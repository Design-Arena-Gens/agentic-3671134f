jQuery(document).ready(function($) {
    var gameInterval;
    var currentRound = null;

    // Initialize game
    function initGame() {
        fetchGameState();
        // Poll every 2 seconds for updates
        gameInterval = setInterval(fetchGameState, 2000);
    }

    // Fetch game state
    function fetchGameState() {
        $.ajax({
            url: cfgData.ajax_url,
            type: 'POST',
            data: {
                action: 'cfg_get_game_state',
                nonce: cfgData.nonce
            },
            success: function(response) {
                if (response.success) {
                    updateGameUI(response.data);
                }
            }
        });
    }

    // Update game UI
    function updateGameUI(data) {
        currentRound = data.round;

        // Update balance
        if (data.user_balance !== undefined) {
            $('#cfg-user-balance').text('₹' + parseFloat(data.user_balance).toFixed(2));
        }

        // Update timer
        if (data.round.status === 'active') {
            var minutes = Math.floor(data.round.time_remaining / 60);
            var seconds = data.round.time_remaining % 60;
            $('#cfg-timer-text').text(padZero(minutes) + ':' + padZero(seconds));

            // Enable booking buttons
            $('.cfg-book-btn').prop('disabled', false);

            // Remove flipped state
            $('.cfg-card').removeClass('flipped winner loss');
        } else if (data.round.status === 'ended') {
            // Show results
            $('#cfg-timer-text').text('Round Ended');
            $('.cfg-book-btn').prop('disabled', true);

            // Flip cards and show winner
            if (data.round.winner_card) {
                showResults(data.round.winner_card);
            }
        } else if (data.round.status === 'waiting') {
            $('#cfg-timer-text').text(data.round.message);
            $('.cfg-book-btn').prop('disabled', true);
            $('.cfg-card').removeClass('flipped winner loss');
        }

        // Update recent winners
        if (data.recent_winners && data.recent_winners.length > 0) {
            updateRecentWinners(data.recent_winners);
        }
    }

    // Show results
    function showResults(winnerCard) {
        $('.cfg-card').each(function() {
            var cardName = $(this).data('card');
            $(this).addClass('flipped');

            if (cardName === winnerCard) {
                $(this).addClass('winner');
                $(this).find('.cfg-winner-image').show();
                $(this).find('.cfg-loss-image').hide();
                $(this).find('.cfg-result-text').text('🏆 WINNER 🎉');
            } else {
                $(this).addClass('loss');
                $(this).find('.cfg-winner-image').hide();
                $(this).find('.cfg-loss-image').show();
                $(this).find('.cfg-result-text').text('LOSS 😔');
            }
        });
    }

    // Update recent winners
    function updateRecentWinners(winners) {
        var html = '';
        winners.forEach(function(winner) {
            var date = new Date(winner.end_time);
            var formattedDate = date.toLocaleDateString('en-US', { month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' });
            html += '<div class="cfg-winner-item">';
            html += '<span class="cfg-winner-card">' + winner.winner_card + '</span>';
            html += '<span class="cfg-winner-time">' + formattedDate + '</span>';
            html += '</div>';
        });
        $('#cfg-winners-list').html(html);
    }

    // Pad zero
    function padZero(num) {
        return num < 10 ? '0' + num : num;
    }

    // Book button click
    $(document).on('click', '.cfg-book-btn', function() {
        var cardName = $(this).data('card');
        var cardImage = $(this).closest('.cfg-card').find('.cfg-card-image').attr('src');

        cfgCurrentCard = cardName;

        $('#cfg-modal-card-name').text(cardName);
        $('#cfg-modal-card-image').attr('src', cardImage);
        $('#cfg-quantity').val(1);
        updateTotalCost();

        $('#cfg-booking-modal').addClass('active');
    });

    // Close modal
    $('.cfg-close').on('click', function() {
        $('#cfg-booking-modal').removeClass('active');
    });

    // Click outside modal
    $(document).on('click', '#cfg-booking-modal', function(e) {
        if ($(e.target).is('#cfg-booking-modal')) {
            $('#cfg-booking-modal').removeClass('active');
        }
    });

    // Quantity controls
    $('#cfg-qty-plus').on('click', function() {
        var qty = parseInt($('#cfg-quantity').val());
        $('#cfg-quantity').val(qty + 1);
        updateTotalCost();
    });

    $('#cfg-qty-minus').on('click', function() {
        var qty = parseInt($('#cfg-quantity').val());
        if (qty > 1) {
            $('#cfg-quantity').val(qty - 1);
            updateTotalCost();
        }
    });

    // Update total cost
    function updateTotalCost() {
        var qty = parseInt($('#cfg-quantity').val());
        var total = qty * cfgCardCost;
        $('#cfg-total-cost').text(total);
    }

    // Confirm booking
    $('#cfg-confirm-booking').on('click', function() {
        var button = $(this);
        var originalText = button.text();

        button.prop('disabled', true).text('Processing...');

        $.ajax({
            url: cfgData.ajax_url,
            type: 'POST',
            data: {
                action: 'cfg_book_card',
                nonce: cfgData.nonce,
                card_name: cfgCurrentCard,
                quantity: $('#cfg-quantity').val()
            },
            success: function(response) {
                if (response.success) {
                    alert(response.data.message);
                    $('#cfg-booking-modal').removeClass('active');

                    // Update balance
                    if (response.data.new_balance !== undefined) {
                        $('#cfg-user-balance').text('₹' + parseFloat(response.data.new_balance).toFixed(2));
                    }

                    // Refresh page to sync state
                    location.reload();
                } else {
                    alert(response.data.message || 'Booking failed');
                }
                button.prop('disabled', false).text(originalText);
            },
            error: function() {
                alert('An error occurred. Please try again.');
                button.prop('disabled', false).text(originalText);
            }
        });
    });

    // Withdrawal form
    $('#cfg-withdrawal-form').on('submit', function(e) {
        e.preventDefault();

        var button = $(this).find('button[type="submit"]');
        var originalText = button.text();

        button.prop('disabled', true).text('Processing...');

        $.ajax({
            url: cfgData.ajax_url,
            type: 'POST',
            data: {
                action: 'cfg_request_withdrawal',
                nonce: cfgData.nonce,
                amount: $('#withdrawal-amount').val(),
                upi_id: $('#upi-id').val()
            },
            success: function(response) {
                if (response.success) {
                    alert(response.data.message);
                    location.reload();
                } else {
                    alert(response.data.message || 'Request failed');
                }
                button.prop('disabled', false).text(originalText);
            },
            error: function() {
                alert('An error occurred. Please try again.');
                button.prop('disabled', false).text(originalText);
            }
        });
    });

    // Start game if on game page
    if ($('.cfg-game-container').length > 0) {
        initGame();
    }
});
