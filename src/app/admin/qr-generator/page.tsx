import { AdminQRGenerator } from '@/components/admin-qr-generator';
import { Toaster } from '@/components/ui/toaster';

export const metadata = {
  title: 'QR Code Generator - Admin Dashboard',
  description: 'Generate customer registration QR codes',
};

export default function AdminQRGeneratorPage() {
  return (
    <div className="container mx-auto px-4 py-8 space-y-6">
      <div>
        <h1 className="text-3xl font-bold tracking-tight">QR Code Generator</h1>
        <p className="text-muted-foreground mt-1">
          Generate unique registration links with QR codes for customer onboarding
        </p>
      </div>
      
      <AdminQRGenerator />
      <Toaster />
    </div>
  );
}