import { Metadata } from 'next';
import { notFound } from 'next/navigation';
import { prisma } from '@/lib/db';
import { CustomerRegistrationForm } from '@/components/customer-registration-form';
import { CustomerProfilePreview } from '@/components/customer-profile-preview';
import type { CustomerToken } from '@/types';

interface PageProps {
  params: Promise<{ token: string }>;
}

async function getCustomerToken(token: string): Promise<CustomerToken | null> {
  const customer = await prisma.customerToken.findUnique({
    where: { token },
  });
  return customer;
}

export async function generateMetadata({ params }: PageProps): Promise<Metadata> {
  const { token } = await params;
  const customer = await getCustomerToken(token);
  
  if (!customer) {
    return { title: 'Invalid Link - Not Found' };
  }

  if (customer.status === 'active') {
    return {
      title: `${customer.fullName || 'Customer Profile'} - Active Profile`,
      description: `View the activated profile for ${customer.fullName || 'this customer'}`,
      openGraph: {
        title: `${customer.fullName || 'Customer Profile'} - Active Profile`,
        description: `View the activated profile for ${customer.fullName || 'this customer'}`,
        type: 'profile',
      },
    };
  }

  return {
    title: 'Complete Your Registration',
    description: 'Activate your account by completing the registration form',
    robots: 'noindex, nofollow',
  };
}

export default async function CustomerTokenPage({ params }: PageProps) {
  const { token } = await params;
  const customer = await getCustomerToken(token);

  if (!customer) {
    notFound();
  }

  const initialData = {
    fullName: customer.fullName ?? undefined,
    email: customer.email ?? undefined,
    phone: customer.phone ?? undefined,
  };

  if (customer.status === 'pending') {
    return (
      <main className="min-h-screen flex items-center justify-center px-4 py-12 bg-gradient-to-b from-background to-muted/50">
        <div className="w-full max-w-md">
          <div className="text-center mb-8">
            <h1 className="text-3xl font-bold tracking-tight">Complete Registration</h1>
            <p className="text-muted-foreground mt-2">
              This link is unique to you. Please fill in your details to activate your profile.
            </p>
          </div>
          <CustomerRegistrationForm token={token} initialData={initialData} />
        </div>
      </main>
    );
  }

  return (
    <main className="min-h-screen px-4 py-12 bg-gradient-to-b from-background to-muted/50">
      <div className="w-full max-w-2xl">
        <div className="text-center mb-8">
          <h1 className="text-3xl font-bold tracking-tight">Customer Profile</h1>
          <p className="text-muted-foreground mt-2">Public profile preview</p>
        </div>
        <CustomerProfilePreview customer={customer} />
      </div>
    </main>
  );
}