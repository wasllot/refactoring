<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;

use Analytics;
use Spatie\Analytics\Period;
use Prestashop, DateTime;

class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;


    /**
     * 
     * Envía una petición a la api de analytics para obtener las sesiones, visitas y urls de las páginas,
     * en base al periodo  especificado.
     * 
     */
    public function get_analytics_data($metrics = 'ga:sessions, ga:pageviews', $dimensions = 'ga:landingPagePath', $period = 1, $date_start, $date_end){
      $daterange_start = \DateTime::createFromFormat('Y-m-d', $date_start);
      $daterange_end = \DateTime::createFromFormat('Y-m-d', $date_end);

        return $results = Analytics::performQuery(
            Period::create($daterange_start, $daterange_end),
            'ga:sessions, ga:pageviews',
            [
                'metrics' => $metrics,
                'dimensions' => $dimensions
            ]
        );
    }

      /**
     * 
     * Envía una petición a la api de analytics para obtener las sesiones y visitas totales
     * 
     */
    public function get_analytics_totals($date_start = null,  $date_end = null){
      $daterange_start = \DateTime::createFromFormat('Y-m-d', $date_start);
      $daterange_end = \DateTime::createFromFormat('Y-m-d', $date_end);

        return $results = Analytics::performQuery(
            Period::create($daterange_start, $daterange_end),
            'ga:sessions, ga:pageviews'
        );
    }

    /**
     * 
     * Extrae la información relacionada a los productos entre las páginas
     * traídas por analytics
     * 
     */

    public function get_product_data_from_results($key, $results = null){

        $urls = [];
      
       if (count($results->getRows()) > 0) {
      
          // Get the entry for the first entry in the first row.
          $rows = $results->getRows();
      
          foreach($rows as $item){
      
            if(strpos($item[0], $key) !== false){
      
              
              $product_id = $this->get_product_id_from_url($item[0]);

              if(is_array($product_id)){

                array_push($item, $product_id['product_id']);
                // array_push($item, $product_id['variation_id']);

              }else{

                array_push($item, $product_id);

              }

              array_push($urls, $item);
      
            }
      
          }
      
        } else {
      
          echo "No results found.<br>";
      
        }
      
        return $urls;
      
      }

      /**
       * 
       * Extrae el ID de la URL del producto traído por analytics
       * 
       */

      public function get_product_id_from_url($product_url){


        if(!empty($product_url)){
    
            $params_url = explode("/", $product_url);
    
            if(sizeof($params_url)>2){
                $product_url = $params_url[2];  
    
            }else{
    
                $product_url = $params_url[1];
            }

    
            $product_url_params = explode("-", $product_url);
    
    
            if(is_numeric($product_url_params[0]) && is_numeric($product_url_params[1])){
                //Variable product
    
                $product_id = $product_url_params[0];
                $variation_id = $product_url_params[1];

                $product_ids = array(
                    "product_id"  => (int)$product_id,
                    "variation_id" => (int)$variation_id,
                );
                
                return $product_ids;
    
            }else{
    
                //Simple product
                $product_id = $product_url_params[0];

                return (int)$product_id;
    
            }
        
    
        }else{
    
            return null;
    
        }
    }
}
