export type CustomerStatus = 'pending' | 'active';

export interface CustomerToken {
  id: string;
  token: string;
  status: CustomerStatus;
  fullName: string | null;
  email: string | null;
  phone: string | null;
  passwordHash: string | null;
  createdAt: Date;
  activatedAt: Date | null;
  updatedAt: Date;
}

export interface CreateCustomerTokenResult {
  token: string;
  url: string;
  qrCodeDataUrl: string;
}

export interface ActivateCustomerInput {
  token: string;
  fullName: string;
  email: string;
  password: string;
  phone?: string;
}

export interface ApiResponse<T = unknown> {
  success: boolean;
  data?: T;
  error?: string;
  message?: string;
  fieldErrors?: Record<string, string[]>;
}