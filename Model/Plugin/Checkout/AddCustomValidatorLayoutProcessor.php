<?php
declare(strict_types=1);
/**
 * Copyright © 2025
 * Piotr Wlosek piotr.wlosekx@gmail.com
 */
namespace M2S\AdvancedValidator\Model\Plugin\Checkout;

use Magento\Framework\Stdlib\ArrayManager;
use Magento\Checkout\Block\Checkout\LayoutProcessorInterface;
use M2S\AdvancedValidator\ViewModel\Config;
use Magento\Framework\Serialize\SerializerInterface;
use M2S\AdvancedValidator\Model\CustomFieldProcessor;

class AddCustomValidatorLayoutProcessor implements LayoutProcessorInterface
{
    /**
     * @param Config $config
     * @param ArrayManager $arrayManager
     * @param SerializerInterface $serializeInterface
     * @param CustomFieldProcessor $customFieldProcessor
     */
    public function __construct(
        protected Config $config,
        protected ArrayManager $arrayManager,
        protected SerializerInterface $serializeInterface,
        protected CustomFieldProcessor $customFieldProcessor,
    ) {
    }

    /**
     * Implement custom validation to jsLayout
     *
     * @param $jsLayout
     * @return array
     */
    public function process($jsLayout): array
    {
        if ($this->config->isEnabled()) {
            $this->implementShippingAddressValidation($jsLayout);
            $this->implementBillingAddressValidation($jsLayout);
        }

        return $jsLayout;
    }

    /**
     * Implement custom validation for shipping address
     *
     * @param $jsLayout
     * @return array
     */
    protected function implementShippingAddressValidation(&$jsLayout): array
    {
        $shippingForm = Config::COMPONENT_PATH . $this->config->getAdvancedShippingAddressPath();

        if (empty($fields = $this->arrayManager->get($shippingForm, $jsLayout))) {
            return $jsLayout;
        }

        $customFields = $this->config->getCustomFieldsValidationJson();
        $this->customFieldProcessor->applyCustomFieldSettings($customFields,  $fields, Config::SHIPPING_FORMS, 'validation');
        $jsLayout = $this->arrayManager->replace($shippingForm, $jsLayout, $fields);
        return $jsLayout;
    }

    /**
     * Implement custom validation for billing address
     *
     * @param $jsLayout
     * @return array
     */
    protected function implementBillingAddressValidation(&$jsLayout): array
    {
        $billingMode = $this->config->getDisplayBillingAddressMode();
        $customFields = $this->config->getCustomFieldsValidationJson();
        if ($billingMode) {
            $billingForm = Config::COMPONENT_PATH . $this->config->getAdvancedBillingAddressPath();
            if (empty($fields = $this->arrayManager->get($billingForm, $jsLayout))) {
                return $jsLayout;
            }

            $this->customFieldProcessor->applyCustomFieldSettings($customFields,  $fields, Config::BILLING_FORMS, 'validation');
            $jsLayout = $this->arrayManager->replace($billingForm, $jsLayout, $fields);

        } else {
            foreach ($this->customFieldProcessor->getPaymentMethods($jsLayout) as $paymentKey => &$paymentMethod) {
                $paymentPath = Config::BILLING_ADDRESS_PAYMENT_METHODS_PATH . '/' . $paymentKey . '/' . 'children/form-fields/children';
                $fields = &$paymentMethod['children']['form-fields']['children'];
                if ($fields === null) {
                    continue;
                }
                $this->customFieldProcessor->applyCustomFieldSettings($customFields,  $fields, Config::BILLING_FORMS, 'validation');
                $jsLayout = $this->arrayManager->replace($paymentPath, $jsLayout, $fields);
            }
        }
        return $jsLayout;
    }
}
