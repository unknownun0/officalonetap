import { NextRequest, NextResponse } from 'next/server';
import { prisma } from '@/lib/db';
import { generateQrSchema } from '@/lib/validations';
import { generateSecureToken, getBaseUrl } from '@/lib/utils';
import QRCode from 'qrcode';

export async function POST(request: NextRequest) {
  try {
    const body = await request.json();
    const validated = generateQrSchema.safeParse(body);

    if (!validated.success) {
      return NextResponse.json(
        { success: false, error: 'Invalid input', fieldErrors: validated.error.flatten().fieldErrors },
        { status: 400 }
      );
    }

    const { prefillName, prefillEmail, prefillPhone } = validated.data;
    const token = generateSecureToken(32);
    const baseUrl = getBaseUrl();
    const registrationUrl = `${baseUrl}/c/${token}`;

    const qrCodeDataUrl = await QRCode.toDataURL(registrationUrl, {
      width: 512,
      margin: 2,
      color: { dark: '#000000', light: '#ffffff' },
      errorCorrectionLevel: 'M',
    });

    const customerToken = await prisma.customerToken.create({
      data: {
        token,
        status: 'pending',
        fullName: prefillName || null,
        email: prefillEmail || null,
        phone: prefillPhone || null,
      },
    });

    return NextResponse.json({
      success: true,
      token: {
        id: customerToken.id,
        token: customerToken.token,
        status: customerToken.status,
        fullName: customerToken.fullName,
        email: customerToken.email,
        phone: customerToken.phone,
        createdAt: customerToken.createdAt,
        activatedAt: customerToken.activatedAt,
      },
      url: registrationUrl,
      qrCodeDataUrl,
    });
  } catch (error) {
    console.error('QR generation error:', error);
    return NextResponse.json(
      { success: false, error: 'Failed to generate QR code' },
      { status: 500 }
    );
  }
}

export async function GET(request: NextRequest) {
  try {
    const tokens = await prisma.customerToken.findMany({
      orderBy: { createdAt: 'desc' },
      take: 50,
    });

    return NextResponse.json({ success: true, tokens });
  } catch (error) {
    console.error('Failed to fetch tokens:', error);
    return NextResponse.json(
      { success: false, error: 'Failed to fetch tokens' },
      { status: 500 }
    );
  }
}

export async function DELETE(request: NextRequest) {
  try {
    const { searchParams } = new URL(request.url);
    const id = searchParams.get('id');

    if (!id) {
      return NextResponse.json({ success: false, error: 'Token ID required' }, { status: 400 });
    }

    await prisma.customerToken.delete({ where: { id } });

    return NextResponse.json({ success: true });
  } catch (error) {
    console.error('Failed to delete token:', error);
    return NextResponse.json(
      { success: false, error: 'Failed to delete token' },
      { status: 500 }
    );
  }
}