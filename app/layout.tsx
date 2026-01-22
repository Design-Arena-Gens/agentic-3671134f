import type { Metadata } from 'next'
import './globals.css'

export const metadata: Metadata = {
  title: 'The Card Flip - Wallet Game',
  description: 'Real-time wallet-based card game',
}

export default function RootLayout({
  children,
}: {
  children: React.ReactNode
}) {
  return (
    <html lang="en">
      <body>{children}</body>
    </html>
  )
}
