<?php
if (!defined('ABSPATH')) {
    exit;
}

$success = isset($_GET['success']);
$error = isset($_GET['error']) ? $_GET['error'] : '';
?>

<div class="wrap">
    <h1>Wallet Management</h1>

    <?php if ($success): ?>
        <div class="notice notice-success is-dismissible">
            <p>Wallet action completed successfully!</p>
        </div>
    <?php endif; ?>

    <?php if ($error === 'user_not_found'): ?>
        <div class="notice notice-error is-dismissible">
            <p>User not found!</p>
        </div>
    <?php endif; ?>

    <?php if ($error === 'insufficient_balance'): ?>
        <div class="notice notice-error is-dismissible">
            <p>Insufficient balance for debit action!</p>
        </div>
    <?php endif; ?>

    <div class="card">
        <h2>Manual Wallet Credit/Debit</h2>

        <form method="post" action="<?php echo admin_url('admin-post.php'); ?>">
            <input type="hidden" name="action" value="cfg_manual_wallet_action">
            <?php wp_nonce_field('cfg_manual_wallet_action'); ?>

            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="user_identifier">User Email or ID</label>
                    </th>
                    <td>
                        <input type="text" id="user_identifier" name="user_identifier" class="regular-text" required>
                        <p class="description">Enter user email or user ID</p>
                    </td>
                </tr>

                <tr>
                    <th scope="row">
                        <label for="amount">Amount (₹)</label>
                    </th>
                    <td>
                        <input type="number" id="amount" name="amount" min="1" step="0.01" class="regular-text" required>
                    </td>
                </tr>

                <tr>
                    <th scope="row">
                        <label for="action_type">Action</label>
                    </th>
                    <td>
                        <select id="action_type" name="action_type" required>
                            <option value="credit">Credit (Add to wallet)</option>
                            <option value="debit">Debit (Deduct from wallet)</option>
                        </select>
                    </td>
                </tr>

                <tr>
                    <th scope="row">
                        <label for="reason">Reason</label>
                    </th>
                    <td>
                        <input type="text" id="reason" name="reason" class="regular-text" required>
                        <p class="description">Brief description of the transaction</p>
                    </td>
                </tr>
            </table>

            <?php submit_button('Process Transaction'); ?>
        </form>
    </div>

    <div class="card" style="margin-top: 20px;">
        <h2>Search User Balance</h2>
        <div id="cfg-user-search">
            <input type="text" id="cfg-search-user" placeholder="Enter user email or ID" class="regular-text">
            <button type="button" id="cfg-search-btn" class="button">Search</button>
        </div>
        <div id="cfg-user-balance-result" style="margin-top: 20px;"></div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    $('#cfg-search-btn').on('click', function() {
        var userIdentifier = $('#cfg-search-user').val();
        if (!userIdentifier) {
            alert('Please enter user email or ID');
            return;
        }

        // This would require additional AJAX endpoint
        $('#cfg-user-balance-result').html('<p>Feature requires additional AJAX implementation</p>');
    });
});
</script>
