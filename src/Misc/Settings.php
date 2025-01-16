<?php

use Hellotext\Api\Webchat;

add_action( 'admin_init', 'hellotext_settings_init' );
function hellotext_settings_init() {

    // Add settings section
    add_settings_section(
        'hellotext_setting_section',
        __( 'settings.title', 'hellotext' ),
        'hellotext_description_section_callback',
        'hellotext-form'
    );

    // Hellotext Business ID
    add_settings_field(
        'hellotext_business_id',
        __( 'settings.business_id', 'hellotext' ),
        'hellotext_business_id_field',
        'hellotext-form',
        'hellotext_setting_section'
    );

    // Hellotext Access Token
    add_settings_field(
        'hellotext_access_token',
        __( 'settings.access_token', 'hellotext' ),
        'hellotext_access_token_field',
        'hellotext-form',
        'hellotext_setting_section'
    );

    // Hellotext Webchat ID
    add_settings_field(
        'hellotext_webchat_id',
        __( 'settings.webchat_id', 'hellotext' ),
        'hellotext_webchat_id_field',
        'hellotext-form',
        'hellotext_setting_section'
    );

    add_settings_field(
        'hellotext_webchat_placement',
        __( 'settings.webchat_placement', 'hellotext' ),
        'hellotext_webchat_placement_field',
        'hellotext-form',
        'hellotext_setting_section'
    );

    add_settings_field(
        'hellotext_webchat_behaviour',
        __( 'settings.webchat_behaviour', 'hellotext' ),
        'hellotext_webchat_behaviour_field',
        'hellotext-form',
        'hellotext_setting_section'
    );


    // Register settings
    register_setting( 'hellotext-form', 'hellotext_business_id' );
    register_setting( 'hellotext-form', 'hellotext_access_token' );
    register_setting( 'hellotext-form', 'hellotext_webchat_id' ); // Corrected ID
}


function hellotext_description_section_callback() {
    $business_id = get_option('hellotext_business_id', null);
    $access_token = get_option('hellotext_access_token', null);

    if ($business_id) {
        echo '<p>' . wp_kses( __( 'description.paragraphs.one', 'hellotext' ), array( 'a' => array( 'href' => array(), 'target' => array(), 'style' => array() ) ) ) . '</p>';
    }

    if ($access_token) {
        echo '<p>' . wp_kses( __( 'description.paragraphs.two', 'hellotext' ), array( 'a' => array( 'href' => array(), 'target' => array(), 'style' => array() ) ) ) . '</p>';
    }
}

function hellotext_business_id_field() {
    ?>
    <input type="text" id="hellotext_business_id" name="hellotext_business_id"
           value="<?php echo esc_attr( get_option('hellotext_business_id') ); ?>"
           style="width: 400px;" />
    <?php
}

function hellotext_access_token_field() {
    ?>
    <textarea id="hellotext_access_token" name="hellotext_access_token"
              style="width: 400px;" rows="5"><?php echo esc_html( get_option('hellotext_access_token') ); ?></textarea>
    <?php
}

function hellotext_webchat_id_field() {
    $ids = Webchat::index();
    $selected = get_option('hellotext_webchat_id', '');

    if (empty($ids)) {
        echo '<p>' . __('webchat_unavailable', 'hellotext') . '</p>';
        return;
    }

    ?>
    <select id="hellotext_webchat" name="hellotext_webchat_id" style="width: 400px;">
        <option value="" <?php selected($selected, ''); ?>>None Selected</option>

        <?php foreach ($ids as $id): ?>
            <option value="<?php echo esc_attr($id); ?>" <?php selected($selected, $id); ?>>
                <?php echo esc_html($id); ?>
            </option>
        <?php endforeach; ?>
    </select>
    <?php
}

function hellotext_webchat_placement_field() {
    $placement = get_option('hellotext_webchat_placement', 'bottom-right'); // 'bottom-right' is the default value

    ?>
    <select id="hellotext_webchat_placement" name="hellotext_webchat_placement" style="width: 400px;">
        <option value="top-left" <?php selected( $placement, 'top-left' ); ?>>
            <?php _e( 'settings.webchat_placement_top-left', 'hellotext' ); ?>
        </option>
        <option value="top-right" <?php selected( $placement, 'top-right' ); ?>>
           <?php _e( 'settings.webchat_placement_top-right', 'hellotext' ); ?>
        </option>
        <option value="bottom-left" <?php selected( $placement, 'bottom-left' ); ?>>
              <?php _e( 'settings.webchat_placement_bottom-left', 'hellotext' ); ?>
        </option>
        <option value="bottom-right" <?php selected( $placement, 'bottom-right' ); ?>>
             <?php _e( 'settings.webchat_placement_bottom-right', 'hellotext' ); ?>
        </option>
    </select>
    <?php
}

function hellotext_webchat_behaviour_field() {
    $behaviour = get_option('hellotext_webchat_behaviour', 'popover'); // 'popover' is the default value

    ?>
    <select id="hellotext_webchat_behaviour" name="hellotext_webchat_behaviour" style="width: 400px;">
        <option value="popover" <?php selected( $behaviour, 'popover' ); ?>>Popover</option>
        <option value="modal" <?php selected( $behaviour, 'modal' ); ?>>Modal</option>
    </select>
    <?php
}


add_action('plugins_loaded', 'init_hellotext');
function init_hellotext () {
	function custom_woocommerce_menu() {
		add_submenu_page(
			'woocommerce',
			'Hellotext',
			'Hellotext',
			'manage_options',
			'wc-hellotext',
			'hellotext_submenu_page_callback'
		);
	}
	add_action('admin_menu', 'custom_woocommerce_menu');

	function hellotext_submenu_page_callback () {
		?>
		<div class="wrap" style="background: white; padding: 32px; padding-top: 8px; border-radius: 8px;">
			<h1 style="color: #FF4C00;">
				<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" width="130" height="43px" viewBox="0 0 224 43" version="1.1"><title>hellotext</title>
					<path d="M28.2760919,19.3224299 L28.2760919,31.1946615 C28.2760919,32.2088722 27.5005873,32.9844452 26.4864658,32.9844452 L21.0579334,32.9844452 C20.043812,32.9844452 19.2683073,32.2088722 19.2683073,31.1946615 L19.2683073,19.9786839 C19.2683073,16.7570733 17.4190271,14.7883113 14.4959712,14.7883113 C11.0360274,14.7883113 8.76916774,17.11503 8.76916774,21.1122135 L8.76916774,31.3139804 C8.76916774,32.3281911 7.99366311,33.1037641 6.97954167,33.1037641 L1.78962607,33.1037641 C0.77550463,33.1037641 0,32.3281911 0,31.3139804 L0,3.45301476 C0,2.43880402 0.77550463,1.6632311 1.78962607,1.6632311 L6.97954167,1.6632311 C7.99366311,1.6632311 8.76916774,2.43880402 8.76916774,3.45301476 L8.76916774,9.06100356 L8.11297151,16.3991166 L8.70951354,16.458776 C9.78328918,11.6860196 13.5415039,8.52406846 18.4331485,8.52406846 C24.3389145,8.52406846 28.2760919,12.8195492 28.2760919,19.3224299 Z M61.1455574,26.3622456 C58.9980061,30.7173858 53.6291279,33.5213803 47.0671656,33.5213803 C37.9997269,33.5213803 31.4377646,28.2116887 31.4377646,20.7542568 C31.4377646,13.6547816 37.2838764,8.52406846 46.0530442,8.52406846 C53.7484363,8.52406846 59.1769687,12.4615925 59.8928191,18.8451542 C60.0121275,19.9190244 59.1769687,20.8735757 58.103193,20.8735757 L40.1472781,20.8735757 C40.4455492,24.8110998 43.1299883,27.2571374 47.186474,27.2571374 C50.2288383,27.2571374 52.8536232,25.7656511 54.1660157,23.4389323 C54.5835951,22.6633594 55.5380623,22.3054026 56.3732212,22.6036999 L60.1314359,23.9758674 C61.0859032,24.2741647 61.622791,25.4076943 61.1455574,26.3622456 Z M40.5648576,17.3536679 L50.706072,17.2343489 C50.0498757,14.7286518 48.3795581,13.5354627 45.8740816,13.5354627 C43.3089509,13.5354627 41.4000164,14.9672896 40.5648576,17.3536679 Z M120.143563,20.9928946 C120.143563,28.2116887 113.641255,33.4617208 104.693125,33.4617208 C95.8046489,33.4617208 89.361995,28.2116887 89.361995,20.9928946 C89.361995,13.7741005 95.8046489,8.52406846 104.693125,8.52406846 C113.641255,8.52406846 120.143563,13.7741005 120.143563,20.9928946 Z M111.076125,21.1122135 C111.076125,17.2940084 108.45134,14.7883113 104.633471,14.7883113 C100.93491,14.7883113 98.3697796,17.2940084 98.3697796,20.9332352 C98.3697796,24.7514403 100.994564,27.2571374 104.812433,27.2571374 C108.570648,27.2571374 111.076125,24.7514403 111.076125,21.1122135 Z M141.559422,26.8395213 C141.261151,25.5866727 139.82945,25.0497376 138.815329,25.8253105 C137.443282,26.8395213 136.190544,27.2571374 134.99746,27.2571374 C132.909563,27.2571374 131.835787,26.0042889 131.835787,23.796889 L131.835787,13.9530789 L139.471525,13.9530789 C140.24703,13.9530789 140.903226,13.2968249 140.903226,12.521252 L140.903226,10.4928305 C140.903226,9.71725757 140.24703,9.06100356 139.471525,9.06100356 L131.835787,9.06100356 L131.835787,3.45301476 C131.835787,2.43880402 131.060282,1.6632311 130.046161,1.6632311 L125.214171,1.6632311 C124.200049,1.6632311 123.424545,2.43880402 123.424545,3.45301476 L123.424545,9.06100356 L123.424545,13.9530789 L123.424545,24.7514403 C123.424545,30.2401102 127.182759,33.5213803 133.565759,33.5213803 C136.727432,33.5213803 139.471525,32.6861479 141.499768,31.0753426 C142.036656,30.6577264 142.275272,29.9418129 142.09631,29.2855589 L141.559422,26.8395213 Z M173.414766,26.3622456 C171.267215,30.7173858 165.898337,33.5213803 159.336374,33.5213803 C150.268936,33.5213803 143.706973,28.2116887 143.706973,20.7542568 C143.706973,13.6547816 149.553085,8.52406846 158.322253,8.52406846 C166.017645,8.52406846 171.446177,12.4615925 172.162028,18.8451542 C172.281336,19.9190244 171.446177,20.8735757 170.372402,20.8735757 L152.416487,20.8735757 C152.714758,24.8110998 155.399197,27.2571374 159.455683,27.2571374 C162.498047,27.2571374 165.122832,25.7656511 166.435224,23.4389323 C166.852804,22.6633594 167.807271,22.3054026 168.64243,22.6036999 L172.400645,23.9758674 C173.414766,24.2741647 173.892,25.4076943 173.414766,26.3622456 Z M152.834066,17.3536679 L162.975281,17.2343489 C162.319084,14.7286518 160.648767,13.5354627 158.14329,13.5354627 C155.637814,13.5354627 153.669225,14.9672896 152.834066,17.3536679 Z M192.862036,20.8139163 L201.094316,12.0439763 C202.168092,10.9104467 201.332933,9.00134411 199.781923,9.00134411 L194.711316,9.00134411 C194.174428,9.00134411 193.637541,9.23998193 193.279615,9.71725757 L188.507279,15.8621815 L183.734943,9.71725757 C183.377018,9.29964138 182.899784,9.00134411 182.303242,9.00134411 L177.113327,9.00134411 C175.562317,9.00134411 174.727159,10.8507872 175.800934,12.0439763 L184.092868,20.8735757 L175.502663,30.0014724 C174.428888,31.135002 175.204392,33.0441046 176.815056,33.0441046 L181.945317,33.0441046 C182.482205,33.0441046 183.019093,32.8054668 183.377018,32.3878506 L188.447625,25.9446294 L193.577886,32.3878506 C193.935812,32.8054668 194.413045,33.0441046 194.949933,33.0441046 L200.139849,33.0441046 C201.690858,33.0441046 202.526017,31.1946615 201.452241,30.0611318 L192.862036,20.8139163 Z M223.941875,29.2258995 L223.345333,26.8395213 C223.047062,25.5866727 221.615362,25.0497376 220.60124,25.8253105 C219.229193,26.8395213 217.976455,27.2571374 216.783371,27.2571374 C214.695474,27.2571374 213.621698,26.0042889 213.621698,23.796889 L213.621698,13.9530789 L221.257436,13.9530789 C222.032941,13.9530789 222.689137,13.2968249 222.689137,12.521252 L222.689137,10.4928305 C222.689137,9.71725757 222.032941,9.06100356 221.257436,9.06100356 L213.621698,9.06100356 L213.621698,3.45301476 C213.621698,2.43880402 212.846194,1.6632311 211.832072,1.6632311 L207.000082,1.6632311 C205.985961,1.6632311 205.210456,2.43880402 205.210456,3.45301476 L205.210456,9.06100356 L205.210456,13.9530789 L205.210456,24.7514403 C205.210456,30.2401102 208.968671,33.5213803 215.35167,33.5213803 C218.513343,33.5213803 221.257436,32.6861479 223.285679,31.0753426 C223.882221,30.5980669 224.120838,29.8821535 223.941875,29.2258995 Z M77.6697714,6.01837134 C78.2663134,5.30245788 78.2663134,4.22858768 77.6101172,3.57233367 L74.5677529,0.529701449 C73.8519024,-0.186212015 72.6588184,-0.186212015 71.942968,0.589360904 C61.3841742,12.4615925 61.3841742,30.5384075 71.942968,42.4106391 C72.5991642,43.186212 73.8519024,43.186212 74.5677529,42.4702986 L77.6101172,39.4276663 C78.2663134,38.7714123 78.3259676,37.6975421 77.6697714,36.9816287 C69.9147251,28.1520293 69.9147251,14.8479707 77.6697714,6.01837134 Z M87.1547896,10.6718089 L84.1124252,7.62917663 C83.3965748,6.91326317 82.1438366,6.91326317 81.4876403,7.74849554 C78.2663134,11.6263601 76.4766873,16.458776 76.4766873,21.5298297 C76.4766873,26.6008834 78.2663134,31.4332993 81.4876403,35.3111639 C82.1438366,36.0867368 83.3965748,36.1463963 84.1124252,35.4304828 L87.1547896,32.3878506 C87.8109858,31.7315966 87.87064,30.7173858 87.3337522,30.0611318 C85.4248177,27.6747536 84.351042,24.6917809 84.351042,21.5894892 C84.351042,18.4871975 85.3651635,15.5042247 87.3337522,13.1178465 C87.87064,12.3422736 87.8109858,11.3280629 87.1547896,10.6718089 Z" id="hellotext" fill="currentColor"></path>
				</svg>
			</h1>

            <?php
              if (empty(get_option( 'permalink_structure' ))) {
                echo <<<HTML
                    <div style="padding: 10px; background-color: #FF4C00; color: #FFFFFF; border-radius: 5px; margin-bottom: 20px;">
                        Important: Please select any <b>Permalink structure</b> other than "Plain" in <a href="/wp-admin/options-permalink.php" style="color: white;">Settings > Permalinks</a>. Otherwise, the plugin will not work.
                    </div>
                HTML;
              }
            ?>
			<form method="POST" action="options.php">
			<?php
				settings_fields( 'hellotext-form' );
				do_settings_sections( 'hellotext-form' );
				submit_button(
				    __('settings.submit', 'hellotext'),
				    null,
				    null,
				    false,
				    array('style' => 'background-color: #FF4C00; color: #FFFFFF; border: none;')
                );
			?>
			</form>
		</div>
	<?php
	}
}
