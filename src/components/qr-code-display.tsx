'use client';

import * as React from 'react';
import { QRCodeSVG } from 'qrcode.react';
import { Download, Copy, Check } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { toast } from '@/hooks/use-toast';
import { cn } from '@/lib/utils';

interface QRCodeDisplayProps {
  url: string;
  token: string;
  onCopy?: () => void;
}

export function QRCodeDisplay({ url, token, onCopy }: QRCodeDisplayProps) {
  const [copied, setCopied] = React.useState(false);
  const [downloaded, setDownloaded] = React.useState(false);

  const handleCopy = async () => {
    try {
      await navigator.clipboard.writeText(url);
      setCopied(true);
      toast({ title: 'Copied!', description: 'QR code link copied to clipboard', variant: 'success' });
      setTimeout(() => setCopied(false), 2000);
      onCopy?.();
    } catch {
      toast({ title: 'Failed to copy', variant: 'destructive' });
    }
  };

  const handleDownload = () => {
    const link = document.createElement('a');
    link.download = `qr-${token}.png`;
    
    fetch(`https://api.qrserver.com/v1/create-qr-code/?size=512x512&data=${encodeURIComponent(url)}`)
      .then(res => res.blob())
      .then(blob => {
        link.href = URL.createObjectURL(blob);
        link.click();
        URL.revokeObjectURL(link.href);
        setDownloaded(true);
        toast({ title: 'Downloaded!', description: 'QR code saved as PNG', variant: 'success' });
        setTimeout(() => setDownloaded(false), 2000);
      })
      .catch(() => {
        toast({ title: 'Download failed', variant: 'destructive' });
      });
  };

  return (
    <Card className="w-full max-w-md">
      <CardHeader>
        <CardTitle className="text-center">Registration QR Code</CardTitle>
      </CardHeader>
      <CardContent className="space-y-4">
        <div className="flex flex-col items-center space-y-4">
          <div className="bg-white p-4 rounded-lg border shadow-inner">
            <QRCodeSVG
              value={url}
              size={256}
              level="M"
              includeMargin={true}
              bgColor="#ffffff"
              fgColor="#000000"
            />
          </div>
          
          <div className="w-full space-y-2">
            <label className="text-xs font-medium text-muted-foreground">Registration Link</label>
            <div className="flex items-center gap-2">
              <input
                type="text"
                readOnly
                value={url}
                className="flex-1 px-3 py-2 text-sm bg-muted border rounded-md font-mono truncate"
                onClick={(e) => e.target.select()}
              />
              <Button variant="outline" size="icon" onClick={handleCopy} aria-label="Copy link">
                {copied ? <Check className="h-4 w-4 text-green-600" /> : <Copy className="h-4 w-4" />}
              </Button>
            </div>
          </div>

          <div className="flex gap-2 w-full">
            <Button variant="outline" onClick={handleDownload} className="flex-1" disabled={downloaded}>
              {downloaded ? (
                <>
                  <Check className="mr-2 h-4 w-4" />
                  Downloaded
                </>
              ) : (
                <>
                  <Download className="mr-2 h-4 w-4" />
                  Download PNG
                </>
              )}
            </Button>
            <Button variant="secondary" onClick={handleCopy} className="flex-1" disabled={copied}>
              {copied ? 'Copied!' : 'Copy Link'}
            </Button>
          </div>
        </div>
      </CardContent>
    </Card>
  );
}