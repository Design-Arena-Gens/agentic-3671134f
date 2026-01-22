<?php
if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;
$table_rounds = $wpdb->prefix . 'cfg_game_rounds';
$table_bookings = $wpdb->prefix . 'cfg_bookings';

$rounds = $wpdb->get_results(
    "SELECT * FROM $table_rounds ORDER BY id DESC LIMIT 50"
);
?>

<div class="wrap">
    <h1>Game History</h1>

    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th>Round ID</th>
                <th>Start Time</th>
                <th>End Time</th>
                <th>Winner Card</th>
                <th>Status</th>
                <th>Total Bookings</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($rounds)): ?>
                <?php foreach ($rounds as $round): ?>
                    <?php
                    $total_bookings = $wpdb->get_var($wpdb->prepare(
                        "SELECT COUNT(*) FROM $table_bookings WHERE round_id = %d",
                        $round->id
                    ));
                    ?>
                    <tr>
                        <td><?php echo esc_html($round->id); ?></td>
                        <td><?php echo esc_html(date('M d, Y H:i:s', strtotime($round->start_time))); ?></td>
                        <td><?php echo esc_html(date('M d, Y H:i:s', strtotime($round->end_time))); ?></td>
                        <td>
                            <?php if ($round->winner_card): ?>
                                <strong><?php echo esc_html($round->winner_card); ?></strong>
                            <?php else: ?>
                                -
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="cfg-status-badge cfg-status-<?php echo esc_attr($round->status); ?>">
                                <?php echo esc_html(ucfirst($round->status)); ?>
                            </span>
                        </td>
                        <td><?php echo esc_html($total_bookings); ?></td>
                        <td>
                            <button type="button" class="button cfg-view-details" data-round-id="<?php echo esc_attr($round->id); ?>">View Details</button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="7">No game rounds found</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<div id="cfg-round-details-modal" style="display: none;">
    <div class="cfg-modal-overlay"></div>
    <div class="cfg-modal-container">
        <span class="cfg-modal-close">&times;</span>
        <div id="cfg-round-details-content"></div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    $('.cfg-view-details').on('click', function() {
        var roundId = $(this).data('round-id');
        // This would require additional AJAX endpoint to fetch round details
        alert('Round details for #' + roundId + ' - Requires AJAX implementation');
    });
});
</script>
