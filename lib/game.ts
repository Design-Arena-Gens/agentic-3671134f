import { getActiveRound, createRound, updateRound, getRoundBookings, updateBooking, getUserBalance, updateBalance, getSetting, getLastRound } from './db';

const CARDS = ['Maruti', 'Ganpati', 'Superman', 'Spiderman'];

export function checkAndUpdateRounds() {
  const now = Math.floor(Date.now() / 1000);
  const activeRound = getActiveRound();

  if (activeRound && now >= activeRound.end_time) {
    const winner = determineWinner(activeRound.id);
    completeRound(activeRound.id, winner);
  }

  const lastRound = getLastRound();
  if (!lastRound || (lastRound.status === 'completed' && lastRound.winner_card)) {
    const pauseDuration = parseInt(getSetting('pause_duration', '5'));
    if (!lastRound || now >= (lastRound.end_time + pauseDuration)) {
      const duration = parseInt(getSetting('round_duration', '60'));
      createRound(now, now + duration);
    }
  }
}

function determineWinner(roundId: number): string {
  const bookings = getRoundBookings(roundId);
  if (bookings.length === 0) {
    return CARDS[Math.floor(Math.random() * CARDS.length)];
  }

  const cardTotals: Record<string, number> = {};
  CARDS.forEach(card => cardTotals[card] = 0);
  bookings.forEach(b => cardTotals[b.card_name] = (cardTotals[b.card_name] || 0) + b.quantity);

  const minValue = Math.min(...Object.values(cardTotals));
  const candidates = Object.keys(cardTotals).filter(card => cardTotals[card] === minValue);
  return candidates[Math.floor(Math.random() * candidates.length)];
}

function completeRound(roundId: number, winnerCard: string) {
  const payout = parseFloat(getSetting('winner_payout', '40'));
  updateRound(roundId, { status: 'completed', winner_card: winnerCard });

  const bookings = getRoundBookings(roundId);
  bookings.forEach(booking => {
    if (booking.card_name === winnerCard) {
      const totalPayout = booking.quantity * payout;
      updateBooking(booking.id, { is_winner: 1, payout: totalPayout });
      const balance = getUserBalance(booking.user_id);
      updateBalance(booking.user_id, balance + totalPayout);
    }
  });
}

export { CARDS, getActiveRound, getRoundBookings };
export { createBooking, getUserBalance, updateBalance, getSetting, getRecentCompletedRounds } from './db';
