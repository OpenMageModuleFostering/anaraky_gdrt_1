<?php
class Anaraky_Gdrt_Block_Script extends Mage_Core_Block_Abstract {
    
    private function getParams()
    {
        $type = $this->getData('pageType');
        $params = array('ecomm_pagetype' => 'siteview');
        switch ($type) {
            case 'home':
                $params = array( 'ecomm_pagetype' => 'home');
                break;
            
            case 'searchresults':
                $params = array( 'ecomm_pagetype' => 'searchresults');
                break;
            
            case 'category':
                $category = Mage::registry('current_category');
                $params = array(
                    'ecomm_pagetype' => 'category',
                    'ecomm_category' => (string)$category->getName()
                );
                unset($category);
                break;
            
            case 'product':
                $product = Mage::registry('current_product');
                $params = array(
                    'ecomm_prodid' => (string)$product->getSku(),
                    'ecomm_pagetype' => 'product',
                    'ecomm_totalvalue' =>  (float)number_format($product->getFinalPrice(), '2', '.', '')
                );
                unset($product);
                break;
            
            case 'cart':
                $cart = Mage::getSingleton('checkout/session')->getQuote();
                $items = $cart->getAllVisibleItems();
                if (count($items) > 0) {
                    $data  = array();
                    
                    foreach ($items as $item)
                    {
                        $data[0][] = (string)$item->getSku();
                        $data[1][] = (int)$item->getQty();
                    }
                    
                    $params = array(
                        'ecomm_prodid' => $data[0],
                        'ecomm_pagetype' => 'cart',
                        'ecomm_quantity' => $data[1],
                        'ecomm_totalvalue' => (float)number_format($cart->getGrandTotal(), '2', '.', '')
                    );
                }
                else
                    $params = array( 'ecomm_pagetype' => 'siteview' );
                
                unset($cart, $items, $item, $data);
                break;
            
            case 'purchase':
                //$cart = Mage::getSingleton('checkout/session')->getQuote();
                //$items = $cart->getAllVisibleItems();

                $order = Mage::getModel('sales/order')->loadByIncrementId(
                                Mage::getSingleton('checkout/session')
                                            ->getLastRealOrderId());
                $items = $order->getAllItems();
                $data  = array();

                foreach ($items as $item)
                {
                    $data[0][] = (string)$item->getSku();
                    $data[1][] = (int)$item->getQtyToInvoice();
                }

                $params = array(
                    'ecomm_prodid' => $data[0],
                    'ecomm_pagetype' => 'purchase',
                    'ecomm_quantity' => $data[1],
                    'ecomm_totalvalue' => (float)number_format($order->getGrandTotal(), '2', '.', '')
                );
                break;
            
            default:
                break;
        }
        
        return $params;
    }
    
    private function paramsToJS($params)
    {
        $result = array();
        
        foreach ($params as $key => $value)
        {
            if (is_array($value) && count($value) == 1)
                $value = $value[0];
            
            if (is_array($value))
            {
                if (is_string($value[0]))
                    $value = '["' . implode('","', $value) . '"]';
                else
                    $value = '[' . implode(',', $value) . ']';
            }
            elseif (is_string($value))
                $value = '"' . $value . '"';

            $result[] = $key . ': ' . $value;
        }
        
        return PHP_EOL . "\t" . implode(',' . PHP_EOL . "\t", $result) . PHP_EOL;
    }
    
    private function paramsToURL($params)
    {
        $result = array();
        
        foreach ($params as $key => $value)
        {
            if (is_array($value))
                $value = implode(',', $value);

            $result[] = $key . '=' . $value;
        }
        
        return urlencode(implode(';', $result));
    }
    
    protected function _toHtml()
    {
        $storeId = Mage::app()->getStore()->getId();
        $gcId = (int)Mage::getStoreConfig('gdrt/general/gc_id', $storeId);
        $gcLabel = trim(Mage::getStoreConfig('gdrt/general/gc_label', $storeId));
        $gcParams = $this->getParams();
        
        $s = PHP_EOL .
            '<script type="text/javascript">' . PHP_EOL .
            '/* <![CDATA[ */' . PHP_EOL .
            'var google_tag_params = {' . $this->paramsToJS($gcParams) . '};' . PHP_EOL .
            'var google_conversion_id = ' . $gcId . ';' . PHP_EOL .
            (!empty($gcLabel) ? 'var google_conversion_label = "' . $gcLabel . '";' . PHP_EOL : '') .
            'var google_custom_params = google_tag_params;' . PHP_EOL .
            'var google_remarketing_only = true;' . PHP_EOL .
            '/* ]]> */' . PHP_EOL .
            '</script>' . PHP_EOL .
            '<script type="text/javascript" src="//www.googleadservices.com/pagead/conversion.js">' . PHP_EOL .
            '</script>' . PHP_EOL .
            '<noscript>' . PHP_EOL .
            '<div style="display:inline;">' . PHP_EOL .
            '<img height="1" width="1" style="border-style:none;" alt="" src="//googleads.g.doubleclick.net/pagead/viewthroughconversion/' . $gcId . '/?value=0' . (!empty($gcLabel) ? '&amp;label=' . $gcLabel : '') . '&amp;guid=ON&amp;script=0&amp;data=' . $this->paramsToURL($gcParams) . '"/>' . PHP_EOL .
            '</div>' . PHP_EOL .
            '</noscript>';
        
        return $s;
    }
}