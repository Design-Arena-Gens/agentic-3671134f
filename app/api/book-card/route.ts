import { NextRequest, NextResponse } from 'next/server';
import { getActiveRound, createBooking, CARDS, getUserBalance, updateBalance, getSetting } from '@/lib/game';

export async function POST(request: NextRequest) {
  try {
    const body = await request.json();
    const { userId, cardName, quantity } = body;

    if (!userId || !cardName || !quantity) {
      return NextResponse.json({ success: false, message: 'Missing required fields' });
    }

    if (!CARDS.includes(cardName)) {
      return NextResponse.json({ success: false, message: 'Invalid card' });
    }

    if (quantity < 1) {
      return NextResponse.json({ success: false, message: 'Invalid quantity' });
    }

    const activeRound = getActiveRound();
    if (!activeRound) {
      return NextResponse.json({ success: false, message: 'No active round' });
    }

    const now = Math.floor(Date.now() / 1000);
    if (now >= activeRound.end_time) {
      return NextResponse.json({ success: false, message: 'Round has ended' });
    }

    const cardCost = parseFloat(getSetting('card_cost', '10'));
    const totalCost = cardCost * quantity;
    const balance = getUserBalance(userId);

    if (balance < totalCost) {
      return NextResponse.json({ success: false, message: 'Insufficient balance' });
    }

    updateBalance(userId, balance - totalCost);
    createBooking(activeRound.id, userId, cardName, quantity, totalCost);

    return NextResponse.json({ success: true, message: 'Booking successful' });
  } catch (error) {
    console.error('Booking error:', error);
    return NextResponse.json({ success: false, message: 'Server error' });
  }
}
