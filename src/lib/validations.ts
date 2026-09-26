import { z } from 'zod';

export const generateQrSchema = z.object({
  prefillName: z.string().min(2).max(255).optional(),
  prefillEmail: z.string().email().max(255).optional(),
  prefillPhone: z.string().max(50).optional(),
});

export const activateCustomerSchema = z.object({
  token: z.string().min(1, 'Token is required'),
  fullName: z.string().min(2, 'Name must be at least 2 characters').max(255),
  email: z.string().email('Invalid email address').max(255),
  password: z.string().min(8, 'Password must be at least 8 characters').max(128),
  confirmPassword: z.string(),
  phone: z.string().max(50).optional(),
}).refine((data) => data.password === data.confirmPassword, {
  message: 'Passwords do not match',
  path: ['confirmPassword'],
});

export type GenerateQrInput = z.infer<typeof generateQrSchema>;
export type ActivateCustomerInput = z.infer<typeof activateCustomerSchema>;