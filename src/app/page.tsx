import Link from 'next/link';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
import { QrCode, Shield, Users, Zap } from 'lucide-react';

export default function HomePage() {
  return (
    <main className="min-h-screen bg-gradient-to-b from-background to-muted/50">
      <div className="container mx-auto px-4 py-20">
        <div className="max-w-3xl mx-auto text-center space-y-8">
          <div className="space-y-4">
            <div className="inline-flex items-center justify-center h-16 w-16 rounded-full bg-primary/10 text-primary mb-6">
              <QrCode className="h-8 w-8" />
            </div>
            <h1 className="text-4xl font-bold tracking-tight">Customer QR Registration</h1>
            <p className="text-lg text-muted-foreground max-w-2xl mx-auto">
              Generate unique QR codes for customer onboarding. Dynamic links that convert from 
              registration forms to public profile previews once activated.
            </p>
          </div>

          <div className="grid gap-6 md:grid-cols-3">
            <Card>
              <CardHeader>
                <Shield className="h-10 w-10 text-primary mb-2" />
                <CardTitle>Secure Tokens</CardTitle>
                <CardDescription>Cryptographically secure unique tokens for each registration link</CardDescription>
              </CardHeader>
            </Card>
            <Card>
              <CardHeader>
                <Zap className="h-10 w-10 text-primary mb-2" />
                <CardTitle>Dynamic States</CardTitle>
                <CardDescription>Single route handles both pending registration and active profile views</CardDescription>
              </CardHeader>
            </Card>
            <Card>
              <CardHeader>
                <Users className="h-10 w-10 text-primary mb-2" />
                <CardTitle>Admin Dashboard</CardTitle>
                <CardDescription>Generate, track, and manage all registration links in one place</CardDescription>
              </CardHeader>
            </Card>
          </div>

          <div className="flex flex-col sm:flex-row gap-4 justify-center">
            <Button asChild size="lg" className="w-full sm:w-auto">
              <Link href="/admin/qr-generator">Open Admin Dashboard</Link>
            </Button>
            <Button asChild variant="outline" size="lg" className="w-full sm:w-auto">
              <Link href="/c/demo-token">Preview Demo Profile</Link>
            </Button>
          </div>

          <div className="pt-8 border-t max-w-2xl mx-auto">
            <h2 className="text-xl font-semibold mb-4">Tech Stack</h2>
            <div className="flex flex-wrap justify-center gap-2 text-sm text-muted-foreground">
              <span className="px-3 py-1 bg-muted rounded-full">Next.js 14 (App Router)</span>
              <span className="px-3 py-1 bg-muted rounded-full">TypeScript</span>
              <span className="px-3 py-1 bg-muted rounded-full">Tailwind CSS</span>
              <span className="px-3 py-1 bg-muted rounded-full">Prisma ORM</span>
              <span className="px-3 py-1 bg-muted rounded-full">PostgreSQL</span>
              <span className="px-3 py-1 bg-muted rounded-full">Zod Validation</span>
              <span className="px-3 py-1 bg-muted rounded-full">Radix UI</span>
              <span className="px-3 py-1 bg-muted rounded-full">Vercel Ready</span>
            </div>
          </div>
        </div>
      </div>
    </main>
  );
}