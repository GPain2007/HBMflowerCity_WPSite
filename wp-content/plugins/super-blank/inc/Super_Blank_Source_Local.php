<?php

namespace SuperBlank;

use \Elementor\TemplateLibrary\Source_Local;
use \Elementor\Core\Settings\Page\Model;

class Super_Blank_Source_Local extends Source_Local
{

	public function importTemplateUsingPHP($filePath)
	{

		if (!file_exists($filePath)) return false;

		$data = include $filePath;

		if (empty($data)) return;

		$content = $data['content'];

		if (! is_array($content)) return;

		// A possibility to modify the content
		$content = apply_filters('super_blank_pre_process_template_content', $content, $data);

		$content = $this->process_export_import_content($content, 'on_import');

		$page_settings = [];

		if (! empty($data['page_settings'])) {
			$page = new Model([
				'id' => 0,
				'settings' => $data['page_settings'],
			]);

			$page_settings_data = $this->process_element_export_import_content($page, 'on_import');

			if (! empty($page_settings_data['settings'])) {
				$page_settings = $page_settings_data['settings'];
			}
		}

		$template_id = $this->save_item([
			'content' => $content,
			'title' => $data['title'],
			'type' => $data['type'],
			'page_settings' => $page_settings,
		]);

		if (is_wp_error($template_id)) {
			return $template_id;
		}

		return $this->get_item($template_id);
	}
}
