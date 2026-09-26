# Customer QR Registration System

A complete Next.js 14 application for generating QR code-based customer registration links that dynamically convert to public profile previews once activated.

## Features

- **Dynamic Route (`/c/[token]`)** - Single route handles both pending registration and active profile states
- **Admin Dashboard** - Generate QR codes, track status, manage links
- **Secure Tokens** - Cryptographically secure 32-byte base64url tokens
- **QR Code Generation** - Server-side generation with downloadable PNGs
- **Password Security** - bcrypt with 12 rounds
- **Form Validation** - Zod schemas on client and server
- **Database-Backed Sessions** - Prisma ORM with PostgreSQL
- **Responsive Design** - Mobile-first Tailwind CSS with Radix UI primitives
- **Accessible** - ARIA labels, focus management, semantic HTML
- **Vercel Ready** - Serverless functions, Edge-compatible

## Tech Stack

- **Framework**: Next.js 14 (App Router)
- **Language**: TypeScript
- **Styling**: Tailwind CSS + tailwindcss-animate
- **UI Components**: Radix UI primitives (Toast, Slot)
- **Icons**: Lucide React
- **Database**: PostgreSQL with Prisma ORM
- **Validation**: Zod
- **QR Codes**: qrcode + qrcode.react
- **Security**: bcryptjs, crypto.randomBytes
- **Deployment**: Vercel

## Project Structure

```
src/
├── app/
│   ├── api/
│   │   ├── qr/generate/route.ts      # POST: create token, GET: list, DELETE: remove
│   │   └── customer/activate/route.ts # POST: activate customer account
│   ├── admin/qr-generator/page.tsx   # Admin dashboard
│   ├── c/[token]/page.tsx            # Dynamic route (pending/active)
│   ├── layout.tsx
│   ├── page.tsx                      # Landing page
│   ├── globals.css
│   └── providers.tsx
├── components/
│   ├── ui/                           # Shadcn-style primitives
│   │   ├── button.tsx
│   │   ├── input.tsx
│   │   ├── card.tsx
│   │   ├── badge.tsx
│   │   ├── table.tsx
│   │   ├── toast.tsx
│   │   └── toaster.tsx
│   ├── qr-code-display.tsx
│   ├── customer-registration-form.tsx
│   ├── customer-profile-preview.tsx
│   └── admin-qr-generator.tsx
├── hooks/
│   └── use-toast.ts
├── lib/
│   ├── db.ts                         # Prisma client
│   ├── utils.ts                      # Helpers (cn, token gen, email mask, etc.)
│   └── validations.ts                # Zod schemas
├── types/
│   └── index.ts
└── prisma/
    └── schema.prisma
```

## Getting Started

### Prerequisites

- Node.js 18+
- PostgreSQL database (local or cloud: Neon, Supabase, Railway, etc.)
- pnpm (recommended) or npm

### Installation

```bash
# Clone and navigate
cd customer-qr-registration

# Install dependencies
npm install

# Set up environment variables
cp .env.example .env
# Edit .env with your DATABASE_URL

# Generate Prisma client
npm run db:generate

# Push schema to database
npm run db:push

# Start development server
npm run dev
```

Visit `http://localhost:3000` for the landing page, `http://localhost:3000/admin/qr-generator` for the admin dashboard.

### Database Schema

```prisma
enum CustomerStatus {
  pending
  active
}

model CustomerToken {
  id           String         @id @default(cuid())
  token        String         @unique @db.VarChar(64)
  status       CustomerStatus @default(pending)
  fullName     String?        @db.VarChar(255)
  email        String?        @unique @db.VarChar(255)
  phone        String?        @db.VarChar(50)
  passwordHash String?        @db.VarChar(255)
  createdAt    DateTime       @default(now())
  activatedAt  DateTime?
  updatedAt    DateTime       @updatedAt

  @@index([status])
  @@index([createdAt])
}
```

## API Endpoints

### Generate QR Code
```http
POST /api/qr/generate
Content-Type: application/json

{
  "prefillName": "John Doe",
  "prefillEmail": "john@example.com",
  "prefillPhone": "+15551234567"
}
```

Response:
```json
{
  "success": true,
  "token": { "id", "token", "status", "fullName", "email", "phone", "createdAt", "activatedAt" },
  "url": "https://yourdomain.com/c/abc123...",
  "qrCodeDataUrl": "data:image/png;base64,..."
}
```

### List Tokens
```http
GET /api/qr/generate
```

### Delete Token
```http
DELETE /api/qr/generate?id=token_id
```

### Activate Customer
```http
POST /api/customer/activate
Content-Type: application/json

{
  "token": "abc123...",
  "fullName": "John Doe",
  "email": "john@example.com",
  "password": "securepassword123",
  "confirmPassword": "securepassword123",
  "phone": "+15551234567"
}
```

## Deployment to Vercel

1. Push to GitHub/GitLab/Bitbucket
2. Import project in Vercel
3. Add environment variables:
   - `DATABASE_URL` - PostgreSQL connection string
   - `NEXT_PUBLIC_APP_URL` - Your Vercel deployment URL
4. Deploy

Vercel will automatically:
- Run `prisma generate` during build
- Deploy API routes as serverless functions
- Configure edge caching for static assets

## Security Considerations

- Tokens are 32-byte cryptographically secure random strings
- Passwords hashed with bcrypt (12 rounds)
- Email uniqueness enforced at database level
- SQL injection prevented via Prisma parameterized queries
- XSS prevented via React's automatic escaping
- Security headers configured in `vercel.json`
- Rate limiting recommended for production (Vercel Edge Config or middleware)

## License

MIT