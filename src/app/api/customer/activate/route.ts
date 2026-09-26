import { NextRequest, NextResponse } from 'next/server';
import { prisma } from '@/lib/db';
import { activateCustomerSchema } from '@/lib/validations';
import { hash } from 'bcryptjs';

export async function POST(request: NextRequest) {
  try {
    const body = await request.json();
    const validated = activateCustomerSchema.safeParse(body);

    if (!validated.success) {
      const fieldErrors = validated.error.flatten().fieldErrors;
      return NextResponse.json(
        { success: false, error: 'Validation failed', fieldErrors },
        { status: 400 }
      );
    }

    const { token, fullName, email, password, phone } = validated.data;

    const customerToken = await prisma.customerToken.findUnique({
      where: { token },
    });

    if (!customerToken) {
      return NextResponse.json(
        { success: false, error: 'Invalid or expired registration link' },
        { status: 404 }
      );
    }

    if (customerToken.status === 'active') {
      return NextResponse.json(
        { success: false, error: 'This registration link has already been used' },
        { status: 400 }
      );
    }

    if (email) {
      const existingEmail = await prisma.customerToken.findFirst({
        where: {
          email,
          status: 'active',
          NOT: { id: customerToken.id },
        },
      });

      if (existingEmail) {
        return NextResponse.json(
          { success: false, error: 'An active account with this email already exists' },
          { status: 409 }
        );
      }
    }

    const passwordHash = await hash(password, 12);

    const updated = await prisma.customerToken.update({
      where: { id: customerToken.id },
      data: {
        status: 'active',
        fullName,
        email,
        phone: phone || null,
        passwordHash,
        activatedAt: new Date(),
      },
    });

    return NextResponse.json({
      success: true,
      message: 'Account activated successfully',
      customer: {
        id: updated.id,
        token: updated.token,
        status: updated.status,
        fullName: updated.fullName,
        email: updated.email,
        phone: updated.phone,
        createdAt: updated.createdAt,
        activatedAt: updated.activatedAt,
      },
    });
  } catch (error) {
    console.error('Activation error:', error);
    return NextResponse.json(
      { success: false, error: 'Failed to activate account. Please try again.' },
      { status: 500 }
    );
  }
}