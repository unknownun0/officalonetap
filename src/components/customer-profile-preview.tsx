'use client';

import * as React from 'react';
import { CheckCircle, User, Mail, Phone, Calendar } from 'lucide-react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { cn, maskEmail, formatDate } from '@/lib/utils';

interface CustomerProfilePreviewProps {
  customer: {
    id: string;
    token: string;
    fullName: string;
    email: string;
    phone: string | null;
    status: 'pending' | 'active';
    createdAt: Date;
    activatedAt: Date | null;
  };
}

export function CustomerProfilePreview({ customer }: CustomerProfilePreviewProps) {
  return (
    <div className="w-full max-w-md mx-auto space-y-4">
      {/* Status Header */}
      <Card className={cn('border-green-200 dark:border-green-800', 'bg-green-50 dark:bg-green-950/50')}>
        <CardContent className="pt-6 pb-4">
          <div className="flex flex-col items-center text-center space-y-3">
            <div className="flex h-16 w-16 items-center justify-center rounded-full bg-green-100 dark:bg-green-900 text-green-600 dark:text-green-400">
              <CheckCircle className="h-8 w-8" />
            </div>
            <div>
              <h3 className="text-2xl font-bold text-green-900 dark:text-green-100">
                Profile Activated
              </h3>
              <p className="text-green-700 dark:text-green-300">
                This customer profile is now active and publicly viewable.
              </p>
            </div>
            <Badge variant="success" className="text-sm px-3 py-1">
              Active since {customer.activatedAt ? formatDate(customer.activatedAt) : 'Unknown'}
            </Badge>
          </div>
        </CardContent>
      </Card>

      {/* Profile Card */}
      <Card>
        <CardHeader className="text-center pb-2">
          <div className="flex h-20 w-20 items-center justify-center rounded-full bg-primary/10 text-primary mx-auto mb-4">
            <User className="h-10 w-10" />
          </div>
          <CardTitle className="text-2xl">{customer.fullName}</CardTitle>
        </CardHeader>
        <CardContent className="space-y-4">
          <div className="space-y-3">
            <div className="flex items-center gap-3 p-3 bg-muted/50 rounded-lg">
              <div className="flex h-10 w-10 items-center justify-center rounded-lg bg-primary/10 text-primary">
                <Mail className="h-5 w-5" />
              </div>
              <div>
                <p className="text-xs font-medium text-muted-foreground">Email</p>
                <p className="font-medium">{maskEmail(customer.email)}</p>
              </div>
            </div>

            {customer.phone && (
              <div className="flex items-center gap-3 p-3 bg-muted/50 rounded-lg">
                <div className="flex h-10 w-10 items-center justify-center rounded-lg bg-primary/10 text-primary">
                  <Phone className="h-5 w-5" />
                </div>
                <div>
                  <p className="text-xs font-medium text-muted-foreground">Phone</p>
                  <p className="font-medium">{customer.phone}</p>
                </div>
              </div>
            )}

            <div className="flex items-center gap-3 p-3 bg-muted/50 rounded-lg">
              <div className="flex h-10 w-10 items-center justify-center rounded-lg bg-primary/10 text-primary">
                <Calendar className="h-5 w-5" />
              </div>
              <div>
                <p className="text-xs font-medium text-muted-foreground">Member Since</p>
                <p className="font-medium">{formatDate(customer.createdAt)}</p>
              </div>
            </div>
          </div>

          <div className="pt-4 border-t flex items-center justify-center gap-2">
            <Badge variant="success" className="gap-1">
              <CheckCircle className="h-3 w-3" />
              Active Profile
            </Badge>
            <span className="text-xs text-muted-foreground">
              Token: {customer.token.slice(0, 8)}...
            </span>
          </div>
        </CardContent>
      </Card>

      {/* Info Message */}
      <div className="text-center text-sm text-muted-foreground p-4 bg-muted/30 rounded-lg">
        <p>This profile has been activated and is publicly accessible via the QR code link.</p>
        <p className="mt-1">Token: <code className="font-mono">{customer.token}</code></p>
      </div>
    </div>
  );
}