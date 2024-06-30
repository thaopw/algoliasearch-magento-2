<?php

namespace Algolia\AlgoliaSearch\Block;

use Algolia\AlgoliaSearch\Helper\AlgoliaHelper;
use Algolia\AlgoliaSearch\Helper\ConfigHelper;
use Algolia\AlgoliaSearch\Helper\Configuration\PersonalizationHelper;
use Algolia\AlgoliaSearch\Helper\Data as CoreHelper;
use Algolia\AlgoliaSearch\Helper\Entity\CategoryHelper;
use Algolia\AlgoliaSearch\Helper\Entity\ProductHelper;
use Algolia\AlgoliaSearch\Helper\Entity\SuggestionHelper;
use Algolia\AlgoliaSearch\Helper\LandingPageHelper;
use Algolia\AlgoliaSearch\Registry\CurrentCategory;
use Algolia\AlgoliaSearch\Service\Product\SortingTransformer;
use Magento\Catalog\Model\Product;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Customer\Model\Context as CustomerContext;
use Magento\Framework\App\ActionInterface;
use Magento\Framework\App\Http\Context as HttpContext;
use Magento\Framework\Data\CollectionDataSourceInterface;
use Magento\Framework\Data\Form\FormKey;
use Magento\Framework\Locale\Currency;
use Magento\Framework\Locale\Format;
use Algolia\AlgoliaSearch\Registry\CurrentProduct;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Framework\Url\Helper\Data as UrlHelper;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Magento\Sales\Model\Order;
use Magento\Search\Helper\Data as CatalogSearchHelper;

class Algolia extends Template implements CollectionDataSourceInterface
{
    protected $priceKey;

    public function __construct(
        protected ConfigHelper          $config,
        protected CatalogSearchHelper   $catalogSearchHelper,
        protected ProductHelper         $productHelper,
        protected Currency              $currency,
        protected Format                $format,
        protected CurrentProduct        $currentProduct,
        protected AlgoliaHelper         $algoliaHelper,
        protected UrlHelper             $urlHelper,
        protected FormKey               $formKey,
        protected HttpContext           $httpContext,
        protected CoreHelper            $coreHelper,
        protected CategoryHelper        $categoryHelper,
        protected SuggestionHelper      $suggestionHelper,
        protected LandingPageHelper     $landingPageHelper,
        protected PersonalizationHelper $personalizationHelper,
        protected CheckoutSession       $checkoutSession,
        protected DateTime              $date,
        protected CurrentCategory       $currentCategory,
        protected SortingTransformer    $sortingTransformer,
        Template\Context                $context,
        array                           $data = []
    )
    {
        parent::__construct($context, $data);
    }

    /**
     * @return \Magento\Store\Model\Store
     */
    public function getStore()
    {
        /** @var \Magento\Store\Model\Store $store */
        $store = $this->_storeManager->getStore();

        return $store;
    }

    public function getConfigHelper()
    {
        return $this->config;
    }

    public function getCoreHelper()
    {
        return $this->coreHelper;
    }

    public function getProductHelper()
    {
        return $this->productHelper;
    }

    public function getCategoryHelper()
    {
        return $this->categoryHelper;
    }

    public function getSuggestionHelper()
    {
        return $this->suggestionHelper;
    }

    public function getCatalogSearchHelper()
    {
        return $this->catalogSearchHelper;
    }

    public function getAlgoliaHelper()
    {
        return $this->algoliaHelper;
    }

    public function getPersonalizationHelper()
    {
        return $this->personalizationHelper;
    }

    public function getCurrencySymbol()
    {
        return $this->currency->getCurrency($this->getCurrencyCode())->getSymbol();
    }
    public function getCurrencyCode()
    {
        return $this->getStore()->getCurrentCurrencyCode();
    }

    public function getPriceFormat()
    {
        return $this->format->getPriceFormat();
    }

    public function getGroupId()
    {
        return $this->httpContext->getValue(CustomerContext::CONTEXT_GROUP);
    }

    public function getPriceKey()
    {
        if ($this->priceKey === null) {
            $currencyCode = $this->getCurrencyCode();

            $this->priceKey = '.' . $currencyCode . '.default';

            if ($this->config->isCustomerGroupsEnabled($this->getStore()->getStoreId())) {
                $groupId = $this->getGroupId();
                $this->priceKey = '.' . $currencyCode . '.group_' . $groupId;
            }
        }

        return $this->priceKey;
    }

    public function getStoreId()
    {
        return $this->getStore()->getStoreId();
    }

    public function getCurrentCategory()
    {
        return $this->currentCategory->get();
    }

    /** @return Product */
    public function getCurrentProduct()
    {
        return $this->currentProduct->get();
    }

    /** @return Order */
    public function getLastOrder()
    {
        return $this->checkoutSession->getLastRealOrder();
    }

    public function getAddToCartParams() : array
    {
        return [
            'action' => $this->_urlBuilder->getUrl('checkout/cart/add', []),
            'formKey' => $this->formKey->getFormKey(),
            'redirectUrlParam' => ActionInterface::PARAM_NAME_URL_ENCODED
        ];
    }

    public function getTimestamp()
    {
        return $this->date->gmtTimestamp('today midnight');
    }

    /**
     * @deprecated This function is deprecated as redirect routes must be derived on the frontend not backend
     */
    protected function getAddToCartUrl($additional = [])
    {
        $continueUrl = $this->urlHelper->getEncodedUrl($this->_urlBuilder->getCurrentUrl());
        $urlParamName = ActionInterface::PARAM_NAME_URL_ENCODED;
        $routeParams = [
            $urlParamName => $continueUrl,
            '_secure' => $this->algoliaHelper->getRequest()->isSecure(),
        ];
        if ($additional !== []) {
            $routeParams = array_merge($routeParams, $additional);
        }
        return $this->_urlBuilder->getUrl('checkout/cart/add', $routeParams);
    }

    protected function getCurrentLandingPage()
    {
        $landingPageId = $this->getRequest()->getParam('landing_page_id');
        if (!$landingPageId) {
            return null;
        }
        return $this->landingPageHelper->getLandingPage($landingPageId);
    }
}
