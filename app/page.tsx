'use client';

import { useState, useEffect } from 'react';

const CARDS = ['Maruti', 'Ganpati', 'Superman', 'Spiderman'];

export default function Home() {
  const [userId, setUserId] = useState<string>('');
  const [balance, setBalance] = useState(0);
  const [timeRemaining, setTimeRemaining] = useState(0);
  const [roundStatus, setRoundStatus] = useState<'active' | 'waiting' | 'ended'>('waiting');
  const [winnerCard, setWinnerCard] = useState<string | null>(null);
  const [recentWinners, setRecentWinners] = useState<any[]>([]);
  const [showModal, setShowModal] = useState(false);
  const [selectedCard, setSelectedCard] = useState<string>('');
  const [quantity, setQuantity] = useState(1);
  const [cardCost, setCardCost] = useState(10);
  const [flippedCards, setFlippedCards] = useState(false);

  // Login handler
  const handleLogin = () => {
    const id = prompt('Enter your User ID (e.g., user123):');
    if (id) {
      setUserId(id);
      localStorage.setItem('userId', id);
      fetchGameState(id);
    }
  };

  // Logout handler
  const handleLogout = () => {
    setUserId('');
    localStorage.removeItem('userId');
  };

  // Check for existing session
  useEffect(() => {
    const storedUserId = localStorage.getItem('userId');
    if (storedUserId) {
      setUserId(storedUserId);
      fetchGameState(storedUserId);
    }
  }, []);

  // Fetch game state
  const fetchGameState = async (uid?: string) => {
    const currentUserId = uid || userId;
    if (!currentUserId) return;

    try {
      const res = await fetch(`/api/game-state?userId=${currentUserId}`);
      const data = await res.json();

      if (data.success) {
        setBalance(data.balance);
        setTimeRemaining(data.timeRemaining);
        setRoundStatus(data.status);
        setWinnerCard(data.winnerCard);
        setRecentWinners(data.recentWinners);
        setCardCost(data.cardCost);

        if (data.status === 'ended' && data.winnerCard) {
          setFlippedCards(true);
        } else {
          setFlippedCards(false);
        }
      }
    } catch (error) {
      console.error('Error fetching game state:', error);
    }
  };

  // Poll game state every 2 seconds
  useEffect(() => {
    if (!userId) return;

    const interval = setInterval(() => {
      fetchGameState();
    }, 2000);

    return () => clearInterval(interval);
  }, [userId]);

  // Book card handler
  const handleBookCard = (card: string) => {
    setSelectedCard(card);
    setQuantity(1);
    setShowModal(true);
  };

  // Confirm booking
  const confirmBooking = async () => {
    try {
      const res = await fetch('/api/book-card', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          userId,
          cardName: selectedCard,
          quantity
        })
      });

      const data = await res.json();

      if (data.success) {
        alert('Booking successful!');
        setShowModal(false);
        fetchGameState();
      } else {
        alert(data.message || 'Booking failed');
      }
    } catch (error) {
      alert('An error occurred');
    }
  };

  // Format timer
  const formatTime = (seconds: number) => {
    const mins = Math.floor(seconds / 60);
    const secs = seconds % 60;
    return `${String(mins).padStart(2, '0')}:${String(secs).padStart(2, '0')}`;
  };

  if (!userId) {
    return (
      <div className="container">
        <div className="login-section">
          <h1>The Card Flip - Wallet Game</h1>
          <p>Please login to play</p>
          <button className="login-btn" onClick={handleLogin}>Login</button>
        </div>
      </div>
    );
  }

  return (
    <div className="container">
      {/* User Info */}
      <div className="user-info">
        <span>User: {userId}</span>
        <button className="logout-btn" onClick={handleLogout}>Logout</button>
      </div>

      {/* Wallet Bar */}
      <div className="wallet-bar">
        <span style={{ fontWeight: 600 }}>Wallet Balance:</span>
        <span className="wallet-amount">₹{balance.toFixed(2)}</span>
      </div>

      {/* Timer */}
      <div className="timer-section">
        <div className="timer-display">
          {roundStatus === 'active' ? formatTime(timeRemaining) : roundStatus === 'ended' ? 'Round Ended' : 'Next game starting soon...'}
        </div>
      </div>

      {/* Cards Grid */}
      <div className="cards-grid">
        {CARDS.map(card => (
          <div key={card} className={`card ${flippedCards ? 'flipped' : ''} ${flippedCards && winnerCard === card ? 'winner' : ''} ${flippedCards && winnerCard !== card ? 'loss' : ''}`}>
            <div className="card-inner">
              <div className="card-front">
                <div className="card-image"></div>
                <h3 className="card-name">{card}</h3>
                <button
                  className="book-btn"
                  onClick={() => handleBookCard(card)}
                  disabled={roundStatus !== 'active'}
                >
                  BOOK
                </button>
              </div>
              <div className="card-back">
                <div className="card-image"></div>
                <h3 className="result-text">
                  {winnerCard === card ? '🏆 WINNER 🎉' : 'LOSS 😔'}
                </h3>
              </div>
            </div>
          </div>
        ))}
      </div>

      {/* Previous Winners */}
      <div className="previous-winners">
        <h3>Previous Winners</h3>
        {recentWinners.length > 0 ? (
          recentWinners.map((winner, idx) => (
            <div key={idx} className="winner-item">
              <span className="winner-card">{winner.winner_card}</span>
              <span>{new Date(winner.end_time * 1000).toLocaleString()}</span>
            </div>
          ))
        ) : (
          <p>No previous winners yet</p>
        )}
      </div>

      {/* Booking Modal */}
      {showModal && (
        <div className="modal active">
          <div className="modal-content">
            <span className="modal-close" onClick={() => setShowModal(false)}>&times;</span>
            <h2>{selectedCard}</h2>
            <div className="quantity-selector">
              <button className="qty-btn" onClick={() => setQuantity(Math.max(1, quantity - 1))}>-</button>
              <input type="number" className="quantity-input" value={quantity} readOnly />
              <button className="qty-btn" onClick={() => setQuantity(quantity + 1)}>+</button>
            </div>
            <div className="cost-display">
              Total Cost: ₹{(quantity * cardCost).toFixed(2)}
            </div>
            <button className="confirm-btn" onClick={confirmBooking}>Confirm Booking</button>
          </div>
        </div>
      )}
    </div>
  );
}
