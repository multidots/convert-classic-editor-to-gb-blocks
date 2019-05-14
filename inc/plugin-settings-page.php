<?php
// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) {
    exit();
}
?>
<div id="dotsstoremain">
    <div class="all-pad">
        <header class="dots-header">
            <div class="dots-header-right">
                <div class="logo-detail">
                    <strong><?php esc_html_e( 'Convert Classic Editor to Gutenberg Blocks', 'convert-classic-editor-to-gutenberg-blocks'); ?></strong>
                </div>
            </div>
            <div class="dots-menu-main">
                <nav>
                    <ul>
                        <li><a class="dotstore_plugin active" href="<?php echo esc_url( admin_url('/admin.php?page=ctgb-classic-to-gutenberg-block') ); ?>"><?php esc_html_e('Getting Started', 'convert-classic-editor-to-gutenberg-blocks'); ?></a></li>
                    </ul>
                </nav>
            </div>
        </header>
        <div class="ctgb-section-left">
            <div class="ctgb-main-table res-cl">
                <h2><?php esc_html_e('Thanks For Installing Convert Classic Editor to Gutenberg Blocks', 'convert-classic-editor-to-gutenberg-blocks'); ?></h2>
                <table class="table-outer">
                    <tbody>
                    <tr>
                        <td class="fr-2">
                            <p class="block gettingstarted"><strong><?php esc_html_e('Getting Started', 'convert-classic-editor-to-gutenberg-blocks'); ?></strong></p>
                            <p class="block textgetting"><?php esc_html_e('Follow following step to convert classic editor post to gutenberg blocks.', 'convert-classic-editor-to-gutenberg-blocks'); ?></p>
                            <p class="block textgetting">
                                <strong><?php esc_html_e('Step 1:', 'convert-classic-editor-to-gutenberg-blocks'); ?></strong> <?php esc_html_e('Go to post editor side and click on "Convert to Gutenberg Blocks" link which is located on the top of the screen.', 'convert-classic-editor-to-gutenberg-blocks'); ?>
                                <span class="gettingstarted">
                                <img src="<?php echo esc_url( plugin_dir_url( dirname( __FILE__ ) ) . 'assets/images/Getting_Started_01.png' ); ?>">
                            </span>
                            </p>
                            <p class="block gettingstarted textgetting">
                                <strong><?php esc_html_e('Step 2:', 'convert-classic-editor-to-gutenberg-blocks'); ?></strong> <?php esc_html_e('You can see the post is converted into the blocks editor as a new draft mode.', 'convert-classic-editor-to-gutenberg-blocks'); ?>
                                <span class="gettingstarted">
                                <img src="<?php echo esc_url( plugin_dir_url( dirname( __FILE__ ) ) . 'assets/images/Getting_Started_02.png' ); ?>">
                            </span>
                            </p>
                            <p class="block gettingstarted textgetting">
                                <strong><?php esc_html_e('Step 3:', 'convert-classic-editor-to-gutenberg-blocks'); ?></strong> <?php esc_html_e('After above step now you need to copy the page Title & Slug from the classic/existing page and delete the page. Update the copied Title & Slug into the converted gutenberg draft page and publish it.', 'convert-classic-editor-to-gutenberg-blocks'); ?>
                            </span>
                            </p>
                        </td>
                    </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>