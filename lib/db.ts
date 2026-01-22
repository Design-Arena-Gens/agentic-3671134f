// In-memory database for demo purposes
interface Round {
  id: number;
  start_time: number;
  end_time: number;
  winner_card: string | null;
  status: string;
}

interface Booking {
  id: number;
  round_id: number;
  user_id: string;
  card_name: string;
  quantity: number;
  cost: number;
  payout: number;
  is_winner: number;
}

const db = {
  balances: new Map<string, number>(),
  transactions: [] as any[],
  rounds: [] as Round[],
  bookings: [] as Booking[],
  settings: new Map<string, string>([
    ['round_duration', '60'],
    ['pause_duration', '5'],
    ['card_cost', '10'],
    ['winner_payout', '40']
  ]),
  roundId: 1,
  bookingId: 1
};

export function getSetting(key: string, defaultValue: string = ''): string {
  return db.settings.get(key) || defaultValue;
}

export function getUserBalance(userId: string): number {
  if (!db.balances.has(userId)) {
    db.balances.set(userId, 100);
  }
  return db.balances.get(userId) || 0;
}

export function updateBalance(userId: string, newBalance: number) {
  db.balances.set(userId, newBalance);
}

export function createRound(startTime: number, endTime: number): number {
  const id = db.roundId++;
  db.rounds.push({ id, start_time: startTime, end_time: endTime, winner_card: null, status: 'active' });
  return id;
}

export function getActiveRound(): Round | undefined {
  const now = Math.floor(Date.now() / 1000);
  return db.rounds.find(r => r.status === 'active' && r.end_time > now);
}

export function getLastRound(): Round | undefined {
  return db.rounds[db.rounds.length - 1];
}

export function updateRound(id: number, updates: Partial<Round>) {
  const round = db.rounds.find(r => r.id === id);
  if (round) Object.assign(round, updates);
}

export function createBooking(roundId: number, userId: string, cardName: string, quantity: number, cost: number) {
  db.bookings.push({ id: db.bookingId++, round_id: roundId, user_id: userId, card_name: cardName, quantity, cost, payout: 0, is_winner: 0 });
}

export function getRoundBookings(roundId: number) {
  return db.bookings.filter(b => b.round_id === roundId);
}

export function updateBooking(id: number, updates: Partial<Booking>) {
  const booking = db.bookings.find(b => b.id === id);
  if (booking) Object.assign(booking, updates);
}

export function getRecentCompletedRounds(limit: number = 3) {
  return db.rounds.filter(r => r.status === 'completed' && r.winner_card !== null).slice(-limit).reverse();
}
