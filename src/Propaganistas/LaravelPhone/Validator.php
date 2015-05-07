<?php namespace Propaganistas\LaravelPhone;

class Validator
{
	/**
	 * Validates a phone number field using libphonenumber.
	 */
	public function phone($attribute, $value, $parameters, $validator)
	{

		$data = $validator->getData();
		
		$type = $parameters[count($parameters)-1];
		if($type == 'mobile' || $type == 'landline'){
			$type = array_pop($parameters);
		}else{
			$type = '';
		}
		// Check if we should validate using a default country or a *_country field.
		if (!empty($parameters)) {
			$countries = $parameters;
		}
		elseif (isset($data[$attribute.'_country'])) {
			$countries = array($data[$attribute.'_country']);
		}
		else {
			return FALSE;
		}

		// Filter out invalid countries.
		foreach ($countries as $key => $country) {
			if (!$this->phone_country($country)) {
				unset($countries[$key]);
			}
		}

		// Now try each country during validation.
		foreach ($countries as $country) {
			$phoneUtil = \libphonenumber\PhoneNumberUtil::getInstance();
			try {
				$phoneProto = $phoneUtil->parse($value, $country);
				if ($phoneUtil->isValidNumberForRegion($phoneProto, $country)) {
					
					// if type is specified, then check for numberType >>> 1 = mobile, 0 = landline
					if($type == 'mobile'){
						if ($phoneUtil->getNumberType($phoneProto) != 1){
							return FALSE;
						}
					}if ($type == 'landline'){
						if ($phoneUtil->getNumberType($phoneProto) != 0){
							return FALSE;
						}
					}
					return TRUE;
				}
			}
			catch (\libphonenumber\NumberParseException $e) {}
		}

		return FALSE;
	}

	/**
	 * Provides some arbitrary validation regarding the _country field to only allow
	 * country codes libphonenumber can handle.
	 * If using a package based on umpirsky/country-list, invalidate the option 'ZZ => Unknown or invalid region'.
	 */
	public function phone_country($country)
	{
		return (strlen($country) === 2 && ctype_alpha($country) && ctype_upper($country) && $country != 'ZZ');
	}

}