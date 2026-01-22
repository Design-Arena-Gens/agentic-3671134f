<?php
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap">
    <h1>Card Flip Game - Settings</h1>

    <form method="post" action="options.php">
        <?php settings_fields('cfg_settings_group'); ?>
        <?php do_settings_sections('cfg_settings_group'); ?>

        <table class="form-table">
            <tr>
                <th scope="row">
                    <label for="cfg_round_duration">Round Duration (seconds)</label>
                </th>
                <td>
                    <input type="number" id="cfg_round_duration" name="cfg_round_duration" value="<?php echo esc_attr(get_option('cfg_round_duration', 60)); ?>" min="1" class="regular-text">
                    <p class="description">How long each game round lasts</p>
                </td>
            </tr>

            <tr>
                <th scope="row">
                    <label for="cfg_pause_duration">Pause Duration (seconds)</label>
                </th>
                <td>
                    <input type="number" id="cfg_pause_duration" name="cfg_pause_duration" value="<?php echo esc_attr(get_option('cfg_pause_duration', 5)); ?>" min="1" class="regular-text">
                    <p class="description">Pause time between rounds</p>
                </td>
            </tr>

            <tr>
                <th scope="row">
                    <label for="cfg_card_cost">Card Cost (₹)</label>
                </th>
                <td>
                    <input type="number" id="cfg_card_cost" name="cfg_card_cost" value="<?php echo esc_attr(get_option('cfg_card_cost', 10)); ?>" min="1" step="1" class="regular-text">
                    <p class="description">Cost per card booking</p>
                </td>
            </tr>

            <tr>
                <th scope="row">
                    <label for="cfg_winner_payout">Winner Payout (₹)</label>
                </th>
                <td>
                    <input type="number" id="cfg_winner_payout" name="cfg_winner_payout" value="<?php echo esc_attr(get_option('cfg_winner_payout', 40)); ?>" min="1" step="1" class="regular-text">
                    <p class="description">Payout per winning card</p>
                </td>
            </tr>

            <tr>
                <th scope="row">
                    <label for="cfg_min_recharge">Minimum Recharge (₹)</label>
                </th>
                <td>
                    <input type="number" id="cfg_min_recharge" name="cfg_min_recharge" value="<?php echo esc_attr(get_option('cfg_min_recharge', 50)); ?>" min="1" step="1" class="regular-text">
                    <p class="description">Minimum wallet recharge amount</p>
                </td>
            </tr>

            <tr>
                <th scope="row">
                    <label for="cfg_min_withdrawal">Minimum Withdrawal (₹)</label>
                </th>
                <td>
                    <input type="number" id="cfg_min_withdrawal" name="cfg_min_withdrawal" value="<?php echo esc_attr(get_option('cfg_min_withdrawal', 200)); ?>" min="1" step="1" class="regular-text">
                    <p class="description">Minimum withdrawal amount</p>
                </td>
            </tr>
        </table>

        <?php submit_button(); ?>
    </form>
</div>
