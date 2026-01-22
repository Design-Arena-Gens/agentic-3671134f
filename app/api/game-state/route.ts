import { NextRequest, NextResponse } from 'next/server';
import { getActiveRound, checkAndUpdateRounds, getUserBalance, getRecentCompletedRounds, getSetting } from '@/lib/game';

export async function GET(request: NextRequest) {
  try {
    const searchParams = request.nextUrl.searchParams;
    const userId = searchParams.get('userId');

    if (!userId) {
      return NextResponse.json({ success: false, message: 'User ID required' });
    }

    // Check and update rounds
    checkAndUpdateRounds();

    const activeRound = getActiveRound();
    const balance = getUserBalance(userId);
    const recentWinners = getRecentCompletedRounds(3);
    const cardCost = parseFloat(getSetting('card_cost', '10'));

    if (!activeRound) {
      return NextResponse.json({
        success: true,
        status: 'waiting',
        timeRemaining: 0,
        balance,
        recentWinners,
        cardCost,
        winnerCard: null
      });
    }

    const now = Math.floor(Date.now() / 1000);
    const timeRemaining = Math.max(0, activeRound.end_time - now);

    if (timeRemaining === 0 && activeRound.winner_card) {
      return NextResponse.json({
        success: true,
        status: 'ended',
        timeRemaining: 0,
        balance,
        recentWinners,
        cardCost,
        winnerCard: activeRound.winner_card
      });
    }

    return NextResponse.json({
      success: true,
      status: 'active',
      timeRemaining,
      balance,
      recentWinners,
      cardCost,
      roundId: activeRound.id,
      winnerCard: null
    });
  } catch (error) {
    console.error('Game state error:', error);
    return NextResponse.json({ success: false, message: 'Server error' });
  }
}
