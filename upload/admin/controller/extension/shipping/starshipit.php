<?php
class ControllerExtensionShippingStarshipit extends Controller {
	private $error = array();

	public function index() {
		$this->load->language('extension/shipping/starshipit');

		$this->document->setTitle($this->language->get('heading_title'));

		$this->load->model('setting/setting');

		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
			$this->model_setting_setting->editSetting('starshipit', $this->request->post);

			$this->session->data['success'] = $this->language->get('text_success');

			$this->response->redirect($this->url->link('extension/extension', 'token=' . $this->session->data['token'] . '&type=shipping', true));
		}

		$data['heading_title'] = $this->language->get('heading_title');

		$data['text_edit'] = $this->language->get('text_edit');
		$data['text_enabled'] = $this->language->get('text_enabled');
		$data['text_disabled'] = $this->language->get('text_disabled');
		$data['text_all_zones'] = $this->language->get('text_all_zones');
		$data['text_none'] = $this->language->get('text_none');

		$data['entry_total'] = $this->language->get('entry_total');
		$data['entry_api_key'] = $this->language->get('entry_api_key');
		$data['entry_domestic_service_codes'] = $this->language->get('entry_domestic_service_codes');
		$data['entry_checkbox_text'] = $this->language->get('entry_checkbox_text');
		$data['entry_safe_drop_suffix'] = $this->language->get('entry_safe_drop_suffix');
		$data['entry_tax_class'] = $this->language->get('entry_tax_class');
		$data['entry_geo_zone'] = $this->language->get('entry_geo_zone');
		$data['entry_status'] = $this->language->get('entry_status');
		$data['entry_sort_order'] = $this->language->get('entry_sort_order');

		$data['help_total'] = $this->language->get('help_total');
		$data['help_domestic_service_codes'] = $this->language->get('help_domestic_service_codes');

		$data['button_save'] = $this->language->get('button_save');
		$data['button_cancel'] = $this->language->get('button_cancel');

		if (isset($this->error['warning'])) {
			$data['error_warning'] = $this->error['warning'];
		} else {
			$data['error_warning'] = '';
		}

		$data['breadcrumbs'] = array();

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/dashboard', 'token=' . $this->session->data['token'], true)
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_extension'),
			'href' => $this->url->link('extension/extension', 'token=' . $this->session->data['token'] . '&type=shipping', true)
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('extension/shipping/starshipit', 'token=' . $this->session->data['token'], true)
		);

		$data['action'] = $this->url->link('extension/shipping/starshipit', 'token=' . $this->session->data['token'], true);

		$data['cancel'] = $this->url->link('extension/extension', 'token=' . $this->session->data['token'] . '&type=shipping', true);

		if (isset($this->request->post['starshipit_total'])) {
			$data['starshipit_total'] = $this->request->post['starshipit_total'];
		} else {
			$data['starshipit_total'] = $this->config->get('starshipit_total');
		}

		if (isset($this->request->post['starshipit_api_key'])) {
			$data['starshipit_api_key'] = $this->request->post['starshipit_api_key'];
		} else {
			$data['starshipit_api_key'] = $this->config->get('starshipit_api_key');
		}

		if (isset($this->request->post['starshipit_domestic_service_codes'])) {
			$data['starshipit_domestic_service_codes'] = $this->request->post['starshipit_domestic_service_codes'];
		} else {
			$data['starshipit_domestic_service_codes'] = $this->config->get('starshipit_domestic_service_codes');
		}

		if (isset($this->request->post['starshipit_checkbox_text'])) {
			$data['starshipit_checkbox_text'] = $this->request->post['starshipit_checkbox_text'];
		} else {
			$data['starshipit_checkbox_text'] = $this->config->get('starshipit_checkbox_text');
		}

		if (isset($this->request->post['starshipit_safe_drop_suffix'])) {
			$data['starshipit_safe_drop_suffix'] = $this->request->post['starshipit_safe_drop_suffix'];
		} else {
			$data['starshipit_safe_drop_suffix'] = $this->config->get('starshipit_safe_drop_suffix');
		}

		if (isset($this->request->post['starshipit_tax_class_id'])) {
			$data['starshipit_tax_class_id'] = $this->request->post['starshipit_tax_class_id'];
		} else {
			$data['starshipit_tax_class_id'] = $this->config->get('starshipit_tax_class_id');
		}

		$this->load->model('localisation/tax_class');

		$data['tax_classes'] = $this->model_localisation_tax_class->getTaxClasses();

		if (isset($this->request->post['starshipit_geo_zone_id'])) {
			$data['starshipit_geo_zone_id'] = $this->request->post['starshipit_geo_zone_id'];
		} else {
			$data['starshipit_geo_zone_id'] = $this->config->get('starshipit_geo_zone_id');
		}

		$this->load->model('localisation/geo_zone');

		$data['geo_zones'] = $this->model_localisation_geo_zone->getGeoZones();

		if (isset($this->request->post['starshipit_status'])) {
			$data['starshipit_status'] = $this->request->post['starshipit_status'];
		} else {
			$data['starshipit_status'] = $this->config->get('starshipit_status');
		}

		if (isset($this->request->post['starshipit_sort_order'])) {
			$data['starshipit_sort_order'] = $this->request->post['starshipit_sort_order'];
		} else {
			$data['starshipit_sort_order'] = $this->config->get('starshipit_sort_order');
		}

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('extension/shipping/starshipit', $data));
	}

	protected function validate() {
		if (!$this->user->hasPermission('modify', 'extension/shipping/starshipit')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}

		return !$this->error;
	}
}
