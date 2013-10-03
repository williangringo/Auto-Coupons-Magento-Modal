<?php

class Xpd_Autocoupons_Model_Standard {

	public function generateCoupon() {
		$generator = Mage::getModel('salesrule/coupon_massgenerator');
		$rule = Mage::getModel('salesrule/rule')->load((int)Mage::getStoreConfig('autocoupons/config/rule'));
		
		switch((int)Mage::getStoreConfig('autocoupons/config/string')){
			case 1:
				$generator->setFormat( Mage_SalesRule_Helper_Coupon::COUPON_FORMAT_ALPHANUMERIC );
				break;
			case 2:
				$generator->setFormat( Mage_SalesRule_Helper_Coupon::COUPON_FORMAT_NUMERIC );
				break;
			case 3:
				$generator->setFormat( Mage_SalesRule_Helper_Coupon::COUPON_FORMAT_ALPHABETICAL );
				break;
		}
		
		$generator->setLength((int)Mage::getStoreConfig('autocoupons/config/size') ? (int)Mage::getStoreConfig('autocoupons/config/size') : 10);
		
		$rule->setCouponCodeGenerator($generator);
		$rule->setCouponType( Mage_SalesRule_Model_Rule::COUPON_TYPE_AUTO );
		
		$coupon = $rule->acquireCoupon();
		$coupon->setType(Mage_SalesRule_Helper_Coupon::COUPON_TYPE_SPECIFIC_AUTOGENERATED)->save();
		$code = $coupon->getCode();
		
		return $code;
	}
	
	public function sendCouponEmail($customerEmail, $customerName = NULL, $coupon) {
		$translate  = Mage::getSingleton('core/translate');
		$email = Mage::getModel('core/email_template');
		$template = Mage::getStoreConfig('autocoupons/config/idtemplate');//Mage::getModel('core/email_template')->loadByCode('Envio de Cupom Promocional')->getTemplateId();
		//Mage::log('Codigo do template: '.$template,null,'autocupons.log');

		$sender  = array(
			'name' => Mage::getStoreConfig('trans_email/ident_support/name', Mage::app()->getStore()->getId()),
			'email' => Mage::getStoreConfig('trans_email/ident_support/email', Mage::app()->getStore()->getId())
		);

		$vars = Array( 'cupom' => $coupon );
		$storeId = Mage::app()->getStore()->getId(); 

		$translate = Mage::getSingleton('core/translate');
		Mage::getModel('core/email_template')
		  ->sendTransactional($template, $sender, $customerEmail, $customerName ? $customerName : 'Descontos para Você' , $vars, $storeId);
		$translate->setTranslateInline(true);

		Mage::log('E-mail Enviado',null,'autocupons.log');
	}
	
	public function setInNewsletter($email) {
		$customer = Mage::getModel('customer/customer')->setWebsiteId(Mage::app()->getStore()->getWebsiteId())->loadByEmail($email);
		
		if ($customer->getId()){
			$subscriber = Mage::getModel('newsletter/subscriber')->loadByEmail($email);
				
			if (!$subscriber->getId() 
				|| $subscriber->getStatus() == Mage_Newsletter_Model_Subscriber::STATUS_UNSUBSCRIBED 
				|| $subscriber->getStatus() == Mage_Newsletter_Model_Subscriber::STATUS_NOT_ACTIVE) {
				
				$subscriber->setStatus(Mage_Newsletter_Model_Subscriber::STATUS_SUBSCRIBED);
				$subscriber->setSubscriberEmail($email);
				$subscriber->setSubscriberConfirmCode($subscriber->RandomSequence());
			}

			$subscriber->setStoreId(Mage::app()->getStore()->getId());
			$subscriber->setCustomerId($customer->getId());
				
			try {
				$subscriber->save();
			}
			catch (Exception $e) {
				throw new Exception($e->getMessage());
			}
		}
		else {
			$subscriber = Mage::getModel('newsletter/subscriber')->subscribe($email);
		}
	}
}

?>