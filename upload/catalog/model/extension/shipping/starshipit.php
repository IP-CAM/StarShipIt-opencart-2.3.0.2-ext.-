<?php
class ModelExtensionShippingStarshipit extends Model {
	function getQuote($address) {
		$this->load->language('extension/shipping/starshipit');

		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "zone_to_geo_zone WHERE geo_zone_id = '" . (int)$this->config->get('starshipit_geo_zone_id') . "' AND country_id = '" . (int)$address['country_id'] . "' AND (zone_id = '" . (int)$address['zone_id'] . "' OR zone_id = '0')");

		if (!$this->config->get('starshipit_geo_zone_id')) {
			$status = true;
		} elseif ($query->num_rows) {
			$status = true;
		} else {
			$status = false;
		}

		if ($this->cart->getSubTotal() < $this->config->get('starshipit_total')) {
			$status = false;
		}

		$error = '';

		$quote_data = array();

		if ($status) {
			$table_weight_gram_id = $this->db->query("SELECT wcd.weight_class_id FROM " . DB_PREFIX . "weight_class_description AS wcd WHERE wcd.unit = \"g\" LIMIT 1");
			$weigth_gram_id = $table_weight_gram_id->row['weight_class_id'];

			$cart_products = $this->cart->getProducts();

			$items = '';
			foreach ($cart_products as $product) {
				if ($items !== '') {
					$items = $items . ',';
				}
				$items = $items . "{
					\"name\":\"" .  $product['name'] . "\",
					\"sku\":\"" .  $product['product_id'] . "\",
					\"quantity\":" .  $product['quantity'] . ",
					\"grams\":" . $this->weight->convert($product['weight'], $product['weight_class_id'], $weigth_gram_id) / $product['quantity'] . ",
					\"price\":" . $product['price'] . "
				}";
			}

			$address1 = $address["address_1"];
			$city = $address["city"];
			$postalCode = $address["postcode"];
			$country = $address["iso_code_2"];
			$province = $address["iso_code_3"];
			$currency = $this->session->data['currency'];

			$postfields = "{
					\"rate\":
					{
						\"destination\":
							{
								\"address1\":\"" . $address1 . "\",
								\"city\":\"" . $city . "\",
								\"postal_code\":\"" . $postalCode . "\",
								\"province\":\"" . $province . "\",
								\"country\":\"" . $country . "\"
							},
						\"items\":[" . $items . "],
						\"currency\":\"" . $currency . "\"
					}
				}";

			$curl = curl_init();

			curl_setopt_array($curl, array(
				CURLOPT_URL => "https://api.shipit.click/rate?apikey=" . $this->config->get('starshipit_api_key') . "&format=json",
				CURLOPT_RETURNTRANSFER => true,
				CURLOPT_ENCODING => "",
				CURLOPT_MAXREDIRS => 10,
				CURLOPT_TIMEOUT => 30,
				CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
				CURLOPT_CUSTOMREQUEST => "POST",
				CURLOPT_POSTFIELDS => $postfields,
				CURLOPT_HTTPHEADER => array(
					"cache-control: no-cache",
					"content-type: application/json"
				),
			));

			$response = curl_exec($curl);
			$err = curl_error($curl);

//================SAMPLE RESPONSE=======================
//			$response = '{
//			  "rates": [
//				{
//				  "service_name": "PARCEL POST1 + SIGNATURE",
//				  "service_code": "FLAT-RATE1",
//				  "total_price": "9.93",
//				  "currency": "AUD",
//				  "min_delivery_date": "2017-03-23 08:24:43",
//				  "max_delivery_date": "2017-03-23 08:24:43"
//				},
//				{
//				  "service_name": "PARCEL POST2 + SIGNATURE",
//				  "service_code": "FLAT-RATE2",
//				  "total_price": "9.90",
//				  "currency": "AUD",
//				  "min_delivery_date": "2017-03-23 08:24:43",
//				  "max_delivery_date": "2017-03-23 08:24:43"
//				},
//				{
//				  "service_name": "PARCEL POST3 + SIGNATURE",
//				  "service_code": "FLAT-RATE3",
//				  "total_price": "0",
//				  "currency": "AUD",
//				  "min_delivery_date": "2017-03-23 08:24:43",
//				  "max_delivery_date": "2017-03-23 08:24:43"
//				},
//				{
//				  "service_name": "PARCEL POST4 + SIGNATURE",
//				  "service_code": "FLAT-RATE4",
//				  "total_price": "9.92",
//				  "currency": "AUD",
//				  "min_delivery_date": "2017-03-23 08:24:43",
//				  "max_delivery_date": "2017-03-23 08:24:43"
//				}
//			  ]
//			}';
//====================================================

			$safedropCodesArr = explode(",", $this->config->get('starshipit_domestic_service_codes'));
			$safedropCodesArr = array_map('trim', $safedropCodesArr);

			if ($response) {
				$response = json_decode($response);

				if (isset($response->responseStatus)) {
					$error = $response->responseStatus;
				} else {

					foreach ($response->rates as $rate) {
						$code = $rate->service_code;
						$description = $rate->service_name;

						$quote_data[$code] = array(
							'code'         => 'starshipit.' . $code,
							'title'        => $description,
							'cost'         => $this->currency->convert($rate->total_price, $rate->currency, $this->config->get('config_currency')),
							'tax_class_id' => $this->config->get('starshipit_tax_class_id'),
							'safe_drop_posible' => array_search($code, $safedropCodesArr) === false ? false : true,
							'text'         => $this->currency->format(
								$this->tax->calculate(
									$this->currency->convert(
										$rate->total_price,
										$rate->currency,
										$this->session->data['currency']
									),
									$this->config->get('starshipit_tax_class_id'),
									$this->config->get('config_tax')
								),
								$this->session->data['currency'],
								1.0000000
							)
						);
					}
				}
			}
		}

		$method_data = array();

		if ($quote_data) {
			$method_data = array(
				'code'       => 'starshipit',
				'title'      => $this->language->get('text_title'),
				'quote'      => $quote_data,
				'safe_drop_suffix'      => $this->config->get('starshipit_safe_drop_suffix'),
				'checkbox_text'      => $this->config->get('starshipit_checkbox_text'),
				'sort_order' => $this->config->get('starshipit_sort_order'),
				'error'      => $error
			);
		}

		return $method_data;
	}
}
