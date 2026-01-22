<?php
if (!defined('ABSPATH')) {
    exit;
}

use CardFlipGame\Database;

$withdrawals = Database::get_withdrawal_requests();
$success = isset($_GET['success']);
?>

<div class="wrap">
    <h1>Withdrawal Requests</h1>

    <?php if ($success): ?>
        <div class="notice notice-success is-dismissible">
            <p>Withdrawal processed successfully!</p>
        </div>
    <?php endif; ?>

    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th>ID</th>
                <th>User</th>
                <th>Amount</th>
                <th>UPI ID</th>
                <th>Request Date</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($withdrawals)): ?>
                <?php foreach ($withdrawals as $withdrawal): ?>
                    <tr>
                        <td><?php echo esc_html($withdrawal->id); ?></td>
                        <td>
                            <?php echo esc_html($withdrawal->display_name); ?>
                            <br>
                            <small><?php echo esc_html($withdrawal->user_email); ?></small>
                        </td>
                        <td>₹<?php echo number_format($withdrawal->amount, 2); ?></td>
                        <td><?php echo esc_html($withdrawal->upi_id); ?></td>
                        <td><?php echo esc_html(date('M d, Y H:i', strtotime($withdrawal->request_date))); ?></td>
                        <td>
                            <span class="cfg-status-badge cfg-status-<?php echo esc_attr($withdrawal->status); ?>">
                                <?php echo esc_html(ucfirst($withdrawal->status)); ?>
                            </span>
                        </td>
                        <td>
                            <?php if ($withdrawal->status === 'pending'): ?>
                                <form method="post" action="<?php echo admin_url('admin-post.php'); ?>" style="display: inline-block;">
                                    <input type="hidden" name="action" value="cfg_process_withdrawal">
                                    <input type="hidden" name="withdrawal_id" value="<?php echo esc_attr($withdrawal->id); ?>">
                                    <input type="hidden" name="withdrawal_action" value="approve">
                                    <?php wp_nonce_field('cfg_process_withdrawal'); ?>
                                    <button type="submit" class="button button-primary" onclick="return confirm('Approve this withdrawal?');">Approve</button>
                                </form>

                                <form method="post" action="<?php echo admin_url('admin-post.php'); ?>" style="display: inline-block;">
                                    <input type="hidden" name="action" value="cfg_process_withdrawal">
                                    <input type="hidden" name="withdrawal_id" value="<?php echo esc_attr($withdrawal->id); ?>">
                                    <input type="hidden" name="withdrawal_action" value="reject">
                                    <?php wp_nonce_field('cfg_process_withdrawal'); ?>
                                    <button type="submit" class="button" onclick="return confirm('Reject this withdrawal?');">Reject</button>
                                </form>
                            <?php else: ?>
                                <?php if ($withdrawal->processed_date): ?>
                                    <small>Processed: <?php echo esc_html(date('M d, Y H:i', strtotime($withdrawal->processed_date))); ?></small>
                                <?php endif; ?>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="7">No withdrawal requests found</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>
