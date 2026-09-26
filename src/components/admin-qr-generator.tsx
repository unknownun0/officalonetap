'use client';

import * as React from 'react';
import { Plus, Loader2, Download, Copy, ExternalLink, Trash2, Eye, X } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Badge } from '@/components/ui/badge';
import { QRCodeDisplay } from '@/components/qr-code-display';
import { toast } from '@/hooks/use-toast';
import { getBaseUrl, maskEmail } from '@/lib/utils';
import type { CustomerToken } from '@/types';

interface RecentToken extends CustomerToken {}

export function AdminQRGenerator() {
  const [isGenerating, setIsGenerating] = React.useState(false);
  const [tokens, setTokens] = React.useState<RecentToken[]>([]);
  const [isLoadingTokens, setIsLoadingTokens] = React.useState(true);
  const [selectedToken, setSelectedToken] = React.useState<RecentToken | null>(null);
  const [showModal, setShowModal] = React.useState(false);
  const [prefillData, setPrefillData] = React.useState({
    name: '',
    email: '',
    phone: '',
  });

  React.useEffect(() => {
    fetchTokens();
  }, []);

  const fetchTokens = async () => {
    try {
      const response = await fetch('/api/qr/generate');
      if (response.ok) {
        const data = await response.json();
        setTokens(data.tokens || []);
      }
    } catch (error) {
      console.error('Failed to fetch tokens:', error);
    } finally {
      setIsLoadingTokens(false);
    }
  };

  const handleGenerate = async () => {
    setIsGenerating(true);
    try {
      const response = await fetch('/api/qr/generate', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(prefillData),
      });

      const data = await response.json();

      if (!response.ok) {
        toast({ title: 'Failed to generate', description: data.error, variant: 'destructive' });
        return;
      }

      toast({ title: 'QR Code Generated!', variant: 'success' });
      setSelectedToken(data.token);
      setShowModal(true);
      setPrefillData({ name: '', email: '', phone: '' });
      fetchTokens();
    } catch {
      toast({ title: 'Error', description: 'Failed to generate QR code', variant: 'destructive' });
    } finally {
      setIsGenerating(false);
    }
  };

  const handleDelete = async (tokenId: string) => {
    if (!confirm('Are you sure you want to delete this registration link? This cannot be undone.')) return;
    
    try {
      const response = await fetch(`/api/qr/generate?id=${tokenId}`, { method: 'DELETE' });
      if (response.ok) {
        toast({ title: 'Deleted', variant: 'success' });
        fetchTokens();
      }
    } catch {
      toast({ title: 'Failed to delete', variant: 'destructive' });
    }
  };

  const statusBadge = (status: string) => (
    <Badge variant={status === 'active' ? 'success' : 'pending'}>
      {status.charAt(0).toUpperCase() + status.slice(1)}
    </Badge>
  );

  return (
    <div className="space-y-6">
      <Card>
        <CardHeader>
          <CardTitle className="flex items-center gap-2">
            <Plus className="h-5 w-5" />
            Generate Registration QR Code
          </CardTitle>
          <CardDescription>
            Create a unique registration link with QR code for customer onboarding
          </CardDescription>
        </CardHeader>
        <CardContent className="space-y-4">
          <div className="grid gap-4 md:grid-cols-3">
            <div>
              <label htmlFor="prefillName" className="block text-sm font-medium mb-1">
                Pre-fill Name (Optional)
              </label>
              <Input
                id="prefillName"
                placeholder="John Doe"
                value={prefillData.name}
                onChange={(e) => setPrefillData((p) => ({ ...p, name: e.target.value }))}
              />
            </div>
            <div>
              <label htmlFor="prefillEmail" className="block text-sm font-medium mb-1">
                Pre-fill Email (Optional)
              </label>
              <Input
                id="prefillEmail"
                type="email"
                placeholder="john@example.com"
                value={prefillData.email}
                onChange={(e) => setPrefillData((p) => ({ ...p, email: e.target.value }))}
              />
            </div>
            <div>
              <label htmlFor="prefillPhone" className="block text-sm font-medium mb-1">
                Pre-fill Phone (Optional)
              </label>
              <Input
                id="prefillPhone"
                type="tel"
                placeholder="+1 (555) 000-0000"
                value={prefillData.phone}
                onChange={(e) => setPrefillData((p) => ({ ...p, phone: e.target.value }))}
              />
            </div>
          </div>
          <Button onClick={handleGenerate} size="lg" loading={isGenerating} className="w-full sm:w-auto">
            <Plus className="mr-2 h-4 w-4" />
            Generate QR Code
          </Button>
        </CardContent>
      </Card>

      <Card>
        <CardHeader>
          <CardTitle>Recent Registration Links</CardTitle>
          <CardDescription>Track the status of generated QR codes</CardDescription>
        </CardHeader>
        <CardContent>
          {isLoadingTokens ? (
            <div className="flex items-center justify-center py-8">
              <Loader2 className="h-8 w-8 animate-spin text-primary" />
            </div>
          ) : tokens.length === 0 ? (
            <div className="text-center py-8 text-muted-foreground">
              <ExternalLink className="h-12 w-12 mx-auto mb-4 opacity-50" />
              <p>No registration links created yet</p>
              <p className="text-sm">Click "Generate QR Code" to create your first link</p>
            </div>
          ) : (
            <div className="overflow-x-auto">
              <Table>
                <TableHeader>
                  <TableRow>
                    <TableHead>Token</TableHead>
                    <TableHead>Status</TableHead>
                    <TableHead>Pre-filled Data</TableHead>
                    <TableHead>Created</TableHead>
                    <TableHead className="text-right">Actions</TableHead>
                  </TableRow>
                </TableHeader>
                <TableBody>
                  {tokens.map((token) => (
                    <TableRow key={token.id}>
                      <TableCell className="font-mono text-sm">
                        {token.token.slice(0, 12)}...
                      </TableCell>
                      <TableCell>{statusBadge(token.status)}</TableCell>
                      <TableCell>
                        <div className="space-y-1 text-sm">
                          {token.fullName && <span>{token.fullName}</span>}
                          {token.email && <span className="text-muted-foreground">{maskEmail(token.email)}</span>}
                          {token.phone && <span className="text-muted-foreground">{token.phone}</span>}
                          {!token.fullName && !token.email && !token.phone && (
                            <span className="text-muted-foreground">—</span>
                          )}
                        </div>
                      </TableCell>
                      <TableCell className="text-sm text-muted-foreground">
                        {new Date(token.createdAt).toLocaleDateString()}
                      </TableCell>
                      <TableCell className="text-right">
                        <div className="flex items-center justify-end gap-2">
                          <Button
                            variant="ghost"
                            size="icon"
                            onClick={() => {
                              const url = `${getBaseUrl()}/c/${token.token}`;
                              navigator.clipboard.writeText(url);
                              toast({ title: 'Link copied', variant: 'success' });
                            }}
                            aria-label="Copy link"
                          >
                            <Copy className="h-4 w-4" />
                          </Button>
                          <Button
                            variant="ghost"
                            size="icon"
                            onClick={() => setSelectedToken(token)}
                            aria-label="View QR code"
                          >
                            <Eye className="h-4 w-4" />
                          </Button>
                          <Button
                            variant="ghost"
                            size="icon"
                            onClick={() => handleDelete(token.id)}
                            aria-label="Delete"
                            className="text-destructive hover:text-destructive"
                          >
                            <Trash2 className="h-4 w-4" />
                          </Button>
                        </div>
                      </TableCell>
                    </TableRow>
                  ))}
                </TableBody>
              </Table>
            </div>
          )}
        </CardContent>
      </Card>

      {showModal && selectedToken && (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
          <div className="relative w-full max-w-md bg-background rounded-lg shadow-xl">
            <div className="flex items-center justify-between p-4 border-b">
              <h2 className="text-lg font-semibold">QR Code Generated</h2>
              <Button
                variant="ghost"
                size="icon"
                onClick={() => setShowModal(false)}
                aria-label="Close"
              >
                <X className="h-5 w-5" />
              </Button>
            </div>
            <QRCodeDisplay
              url={`${getBaseUrl()}/c/${selectedToken.token}`}
              token={selectedToken.token}
            />
          </div>
        </div>
      )}
    </div>
  );
}