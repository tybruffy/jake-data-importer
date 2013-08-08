<?php

Class JDI_SettingsHTML extends JDI_PluginObject {

	private $messages = array();

	function __construct() {
		self::plugin_info();
		wp_enqueue_style("importer-css", self::$plugin_url."/assets/css/base.css");

		$this->processor = new JDI_Processor( $this, $_POST, $_FILES );
	}

	public function add_message( $type, $text ) {
		$this->messages[$type] = $text;
	}

	private function messages() {
		if ( !empty( $this->messages) ) {
			foreach ($this->messages as $type => $text) {
				echo sprintf('<div class="form-message %s" id="message"><p>%s</p></div>', $type, $text);
			}	
		}
	}

	public function display() {
		?>
		<div id="icon-options-general" class="icon32"><br></div>
		<h2 class="nav-tab-wrapper">
			Jake Data Importer
		</h2>

		<?php $this->messages(); ?>

		<div class="section">
			<form method="post" enctype="multipart/form-data">
				<div class="form-row">
					<div class="form-inline form12">
						<input type="file" name="post-list" />
					</div>
				</div>
				<div class="form-row">
					<div clas="form-inline">
						<input type="hidden" name="jdi-import" value="true" />
						<input type="submit" value="Submit" />
					</div>
				</div>
			</form>
		</div>
		<?php
	}

}