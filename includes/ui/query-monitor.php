<?php

defined( '\\ABSPATH' ) || exit;

?>
<?php echo $this->before_non_tabular_output(); ?>

    <section>
        <h3>Status</h3>
        <p class="qm-ltr"><code><?php echo esc_html( $data['status'] ); ?></code></p>
    </section>

    <section>
        <h3>Hit Ratio</h3>
        <p class="qm-ltr"><code><?php echo $data['ratio']; ?>%</code></p>
    </section>

    <section>
        <h3>Hits</h3>
        <p class="qm-ltr"><code><?php echo $data['hits']; ?></code></p>
    </section>

    <section>
        <h3>Misses</h3>
        <p class="qm-ltr"><code><?php echo $data['misses']; ?></code></p>
    </section>

    <section>
        <h3>Size</h3>
        <p class="qm-ltr"><code><?php echo size_format( $data['bytes'], 2 ); ?></code></p>
    </section>

</div>

<?php if ( ! empty( $data['errors'] ) ) : ?>
    <div class="qm-boxed qm-boxed-wrap">

        <section>
            <h3>Errors</h3>

            <table>
                <tbody>
                    <?php foreach ( $data['errors'] as $error ) : ?>
                        <tr class="qm-warn">
                            <td class="qm-ltr qm-wrap">
                                <span class="dashicons dashicons-warning" aria-hidden="true"></span>
                                <?php echo esc_html( $error ); ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </section>

    </div>
<?php endif; ?>

<div class="qm-boxed qm-boxed-wrap">

    <?php if ( ! empty( $data['groups']['global'] ) ) : ?>
        <section>
            <h3>Global Groups</h3>

            <ul class="qm-ltr">
                <?php foreach ( $data['groups']['global'] as $group ) : ?>
                    <li>
                        <?php echo esc_html( $group ); ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        </section>
    <?php endif; ?>

    <?php if ( ! empty( $data['groups']['non_persistent'] ) ) : ?>
        <section>
            <h3>Non-persistent Groups</h3>

            <ul class="qm-ltr">
                <?php foreach ( $data['groups']['non_persistent'] as $group ) : ?>
                    <li>
                        <?php echo esc_html( $group ); ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        </section>
    <?php endif; ?>

    <?php if ( ! empty( $data['groups']['unflushable'] ) ) : ?>
        <section>
            <h3>Unflushable Groups</h3>

            <ul class="qm-ltr">
                <?php foreach ( $data['groups']['unflushable'] as $group ) : ?>
                    <li>
                        <?php echo esc_html( $group ); ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        </section>
    <?php endif; ?>

    <?php if ( ! empty( $data['meta'] ) ) : ?>
        <section>
            <h3>Metadata</h3>

            <table>
                <tbody>
                    <?php foreach ( $data['meta'] as $label => $value ) : ?>
                        <tr>
                            <th scope="row"><?php echo esc_html( $label ); ?></th>
                            <td class="qm-ltr qm-wrap"><?php echo esc_html( $value ); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </section>
    <?php endif; ?>

<?php echo $this->after_non_tabular_output(); ?>
