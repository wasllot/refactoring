<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Product;
use App\Metric;
use Mail, DB;
use Carbon\Carbon;

use App\Mail\NotificationMail;
use \App\Laravue\JsonResponse;
use Prestashop, DateTime, DateTimeZone, Response;

set_time_limit(0);

class ProductController extends Controller
{
    public function index(){


        /**
        * Resta un día a la fecha actual, ya que la información que se obtiene de analytics es de el día anterior
        */

       $datenow =  new DateTime('', new DateTimeZone('America/Santiago')); 
       $datenow->modify("-1 day");

       $date = $datenow->format("Y-m-d");

        /*
         * Obtiene el total de páginas vistas, las sesiones y las urls de las páginas visitadas
         */

        $results = $this->get_product_pages($date, $date);



       $product_data = [];

       foreach($results as $result){

        $product_id = $result[3];
        $product_stock_available_id = null;
        
            
            if($product_id){

                $product_url = $result[0];
                $product_sessions = $result[1];
                $product_page_views = $result[2];

                try {
                    
                    $astroXMLProduct = Prestashop::getSchema('products/'.$product_id);

                    /**
                     * ID de producto en stock
                     * Actualmente en prestashop los productos cuentan con dos IDs. Su Id de producto y el ID en stock.
                     * Para consultar el stock del producto, es necesario obtener el Stock Available ID del mismo
                     */

                    $product_id_response = (int)$astroXMLProduct->product->associations->stock_availables->stock_available->id;

                    $astroXMLStockAvailable = Prestashop::getSchema('stock_availables/'.$product_id_response);
                    $product_quantity = (int)$astroXMLStockAvailable->stock_available->quantity;

                    $data = array(
                        "product_sessions" => $product_sessions,
                        "product_views" => $product_page_views,
                        "product_url" => "https://astrogrowshop.cl".$product_url,
                        "product_said" => $product_id_response,
                        "product_stock" => $product_quantity,                        
                        "analytics_date" => $date
                    );

                    array_push($product_data, $data);

                } catch (\Throwable $th) {


                    throw $th;
                }


            }
       }

       $this->save_product_data($product_data);

       $this->save_metrics($this->metrics($date, $date));



       Mail::to(array("rei.vzl@gmail.com", "oscar.lopez@astrogrowshop.cl"))->send(new NotificationMail());
        
    }


   /** 
    * 
    * A través de la api de Analytics, hace un análisis de las páginas visitadas para obtener
    * las págianas de productos en específico
    *
    */

    public function get_product_pages($date_start = null, $date_end = null){
        /**
         * 
         * Obtiene el total de páginas vistas, las sesiones y las urls de las páginas visitadas
         * 
         */
           

         $results = $this->get_analytics_data('ga:sessions, ga:pageviews','ga:landingPagePath', 1, $date_start, $date_end);
       
        /**
         * 
         * Separa las url de páginas pertenecientes a productos
         * Se le envía el parámetro que diferencia a la URL de productos de las demás
         * 
         */

         $products_data = $this->get_product_data_from_results('html', $results);

        return $products_data;
        
    }


    /**
     * 
     * Guarda el arreglo de productos en la base de datos
     * 
     */

    public function save_product_data($product_data = null){

        if(count($product_data)>0){

            try {
                
                Product::insert($product_data);

                
    
            } catch (\Throwable $th) {
                
                throw $th;
            }

        }

    }

    /**
     * 
     * Guarda las metricas de un dias en la base de datos
     * 
     */

    public function save_metrics($metrics = null){

        if($metrics){

            try {
                
                Metric::insert($metrics);

                
    
            } catch (\Throwable $th) {
                
                throw $th;
            }

        }

    }

    public function view($date_start = null, $date_end = null){
        
        /**
         * 
         * Si no recibe ninguna alguna fecha, coloca la de ayer por defecto 
         * 
        */

        
        if(!$date_start || !$date_end){

            $datenow =  new DateTime('', new DateTimeZone('America/Santiago')); 
            $datenow->modify("-1 day");
     
            $date = $datenow->format("Y-m-d");

            $date_start = $date;
            $date_end = $date;

        }else{

        /**
         * 
         * Si recibe una fecha, la coloca en el formato correcto
         * 
        */

            $date_start_aux = explode("-", $date_start);
            $date_end_aux = explode("-", $date_end);

            if($date_start_aux[1] <10 ){

                $date_start = $date_start_aux[0]."-0".$date_start_aux[1]."-".$date_start_aux[2]; 
            }
            
            if($date_end_aux[1] <10 ){

                $date_end = $date_end_aux[0]."-0".$date_end_aux[1]."-".$date_end_aux[2]; 
            }


            //echo json_encode("Hora de inciio: ".$date_start." - Hora fin: ".$date_end);

        }

        /**
         * 
         * Comprobando si las fechas están en el orden correcto 
         * 
        */

        if($date_start > $date_end){

            $aux = $date_end;
            $date_end = $date_start;
            $date_start = $aux;

        }
        

        $rowsNumber = 10;
        $data = [];

        $total_product_views = 0;
        $total_product_sessions = 0;
        $products_stock = 0;

        $total_views_outs=0;
        $total_sessions_outs=0;
        $products_out_stock = 0;

        /**
         * 
         * Inicia el proceso de análisis de prodctos mediante a api de analytics y prestashop 
         * 
        */
        
        $products = Product::select(
                                    "product_url",  
                                    DB::raw("SUM(product_sessions) as total_sessions"),
                                    DB::raw("SUM(product_views) as total_views"),
                                    DB::raw("AVG(product_stock) as total_stock"),

                                )
                            ->where("analytics_date",'>=', $date_start)
                            ->where("analytics_date",'<=', $date_end)
                            ->where("product_stock",'=', 0)
                            ->groupBy("product_url")
                            ->limit(20)
                            ->get();

        $metrics = Metric::select(
                                
                                "grouped",  
                                DB::raw("SUM(total_views) as total_views"),
                                DB::raw("SUM(total_sessions) as total_sessions"),
                                DB::raw("SUM(total_product_views) as total_product_views"),
                                DB::raw("SUM(total_product_sessions) as total_product_sessions"),
                                DB::raw("SUM(total_views_outs) as total_views_outs"),
                                DB::raw("SUM(total_sessions_outs) as total_sessions_outs"),
                                DB::raw("AVG(products_out_stock) as avg_products_out_stock"),
                                DB::raw("AVG(products_stock) as avg_products_stock"),
                                DB::raw("AVG(total_products) as avg_products_total_products"),
                                DB::raw("AVG(percent_product_stock) as avg_percent_product_stock"),
                                DB::raw("AVG(percent_product_outs) as avg_percent_product_outs"),

                            )
                        ->where("analytics_date",'>=', $date_start)
                        ->where("analytics_date",'<=', $date_end)
                        ->groupBy("grouped")
                        ->first();

        if($metrics){

            $metrics->total_views = (int)$metrics->total_views;
            $metrics->total_sessions = (int)$metrics->total_sessions;
            $metrics->total_product_views = $metrics->total_product_views;
            $metrics->total_product_sessions = (int)$metrics->total_product_sessions;
            $metrics->total_views_outs = (int)$metrics->total_views_outs;
            $metrics->total_sessions_outs = (int)$metrics->total_sessions_outs;
            $metrics->avg_products_total_products = (int)$metrics->avg_products_total_products;
            $metrics->avg_products_out_stock = (int)$metrics->avg_products_out_stock;
            $metrics->avg_percent_product_stock = round($metrics->avg_percent_product_stock, 1);
            $metrics->avg_percent_product_outs = round($metrics->avg_percent_product_outs, 1);

        }else{

            $metrics = [
                'total_views' => 0,
                'total_sessions' => 0,
                'total_product_views' => 0,
                'total_product_sessions' => 0,
                'total_views_outs' => 0,
                'total_sessions_outs' => 0,
                'avg_products_out_stock' => 0,
                'avg_products_stock' => 0,
                'avg_total_products' => 0,
                'avg_percent_product_stock' => 0,
                'avg_percent_product_outs' => 0,
                'analytics_date' => null
            ];
            
        }

        foreach($products as $product){

                $total_views_outs += $product->total_views;
                $total_sessions_outs += $product->total_sessions;
                $products_out_stock++;

                $row = [
                    'id' => $products_out_stock,
                    'views' => $product->total_views,
                    'sessions' => $product->total_sessions,
                    'product_url' => $product->product_url,

                ];
        
                array_push($data, $row);
           
        }

        $total = Product::select(
                                "product_url",  
                                DB::raw("SUM(product_sessions) as total_sessions"),
                                DB::raw("SUM(product_views) as total_views"),
                                DB::raw("AVG(product_stock) as total_stock"),

                            )
                        ->where("analytics_date",'>=', $date_start)
                        ->where("analytics_date",'<=', $date_end)
                        ->where("product_stock",'=', 0)
                        ->groupBy("product_url")
                        ->get();



        $date_string = "del ".$date_start;
        
        if($date_start != $date_end) $date_string .= " al ".$date_end;
    
      return response()->json(new JsonResponse([
          'items' => $data, 
          'total' => sizeof($total), 
          'metrics' => $metrics, 
          'date' => $date_string, 
          'date_start' => $date_start,
          'date_end' => $date_end
          
        ]));

    }

    /**
     * 
     * Muestra la lista de productos en stock
     * 
    */ 

    public function view_products_in_stock($date_start = null, $date_end = null){
        
        /**
         * 
         * Si no recibe ninguna alguna fecha, coloca la de ayer por defecto 
         * 
        */

        
        if(!$date_start || !$date_end){

            $datenow =  new DateTime('', new DateTimeZone('America/Santiago')); 
            $datenow->modify("-1 day");
     
            $date = $datenow->format("Y-m-d");

            $date_start = $date;
            $date_end = $date;

        }else{

        /**
         * 
         * Si recibe una fecha, la coloca en el formato correcto
         * 
        */

            $date_start_aux = explode("-", $date_start);
            $date_end_aux = explode("-", $date_end);

            if($date_start_aux[1] <10 ){

                $date_start = $date_start_aux[0]."-0".$date_start_aux[1]."-".$date_start_aux[2]; 
            }
            
            if($date_end_aux[1] <10 ){

                $date_end = $date_end_aux[0]."-0".$date_end_aux[1]."-".$date_end_aux[2]; 
            }


            //echo json_encode("Hora de inciio: ".$date_start." - Hora fin: ".$date_end);

        }

        /**
         * 
         * Comprobando si las fechas están en el orden correcto 
         * 
        */

        if($date_start > $date_end){

            $aux = $date_end;
            $date_end = $date_start;
            $date_start = $aux;

        }
        

        $rowsNumber = 10;
        $data = [];

        $total_product_views = 0;
        $total_product_sessions = 0;
        $products_stock = 0;

        $total_views_outs=0;
        $total_sessions_outs=0;
        $products_out_stock = 0;

        /**
         * 
         * Inicia el proceso de análisis de prodctos mediante a api de analytics y prestashop 
         * 
        */
        
        $products = Product::select(
                                    "product_url",  
                                    DB::raw("SUM(product_sessions) as total_sessions"),
                                    DB::raw("SUM(product_views) as total_views"),
                                    DB::raw("AVG(product_stock) as total_stock"),

                                )
                            ->where("analytics_date",'>=', $date_start)
                            ->where("analytics_date",'<=', $date_end)
                            ->where("product_stock",'>', 0)
                            ->groupBy("product_url")
                            ->limit(20)
                            ->get();

        $metrics = Metric::select(
                                
                                "grouped",  
                                DB::raw("SUM(total_views) as total_views"),
                                DB::raw("SUM(total_sessions) as total_sessions"),
                                DB::raw("SUM(total_product_views) as total_product_views"),
                                DB::raw("SUM(total_product_sessions) as total_product_sessions"),
                                DB::raw("SUM(total_views_outs) as total_views_outs"),
                                DB::raw("SUM(total_sessions_outs) as total_sessions_outs"),
                                DB::raw("AVG(products_out_stock) as avg_products_out_stock"),
                                DB::raw("AVG(products_stock) as avg_products_stock"),
                                DB::raw("AVG(total_products) as avg_products_total_products"),
                                DB::raw("AVG(percent_product_stock) as avg_percent_product_stock"),
                                DB::raw("AVG(percent_product_outs) as avg_percent_product_outs"),

                            )
                        ->where("analytics_date",'>=', $date_start)
                        ->where("analytics_date",'<=', $date_end)
                        ->groupBy("grouped")
                        ->first();

        if($metrics){

            $metrics->total_views = (int)$metrics->total_views;
            $metrics->total_sessions = (int)$metrics->total_sessions;
            $metrics->total_product_views = $metrics->total_product_views;
            $metrics->total_product_sessions = (int)$metrics->total_product_sessions;
            $metrics->total_views_outs = (int)$metrics->total_views_outs;
            $metrics->total_sessions_outs = (int)$metrics->total_sessions_outs;
            $metrics->avg_products_total_products = (int)$metrics->avg_products_total_products;
            $metrics->avg_products_out_stock = (int)$metrics->avg_products_out_stock;
            $metrics->avg_percent_product_stock = round($metrics->avg_percent_product_stock, 1);
            $metrics->avg_percent_product_outs = round($metrics->avg_percent_product_outs, 1);

        }else{

            $metrics = [
                'total_views' => 0,
                'total_sessions' => 0,
                'total_product_views' => 0,
                'total_product_sessions' => 0,
                'total_views_outs' => 0,
                'total_sessions_outs' => 0,
                'avg_products_out_stock' => 0,
                'avg_products_stock' => 0,
                'avg_total_products' => 0,
                'avg_percent_product_stock' => 0,
                'avg_percent_product_outs' => 0,
                'analytics_date' => null
            ];
            
        }

        foreach($products as $product){

                $total_views_outs += $product->total_views;
                $total_sessions_outs += $product->total_sessions;
                $products_out_stock++;

                $row = [
                    'id' => $products_out_stock,
                    'views' => $product->total_views,
                    'sessions' => $product->total_sessions,
                    'product_url' => $product->product_url,

                ];
        
                array_push($data, $row);
           
        }

        $total = Product::select(
                                "product_url",  
                                DB::raw("SUM(product_sessions) as total_sessions"),
                                DB::raw("SUM(product_views) as total_views"),
                                DB::raw("AVG(product_stock) as total_stock"),

                            )
                        ->where("analytics_date",'>=', $date_start)
                        ->where("analytics_date",'<=', $date_end)
                        ->where("product_stock",'>', 0)
                        ->groupBy("product_url")
                        ->get();



        $date_string = "del ".$date_start;
        
        if($date_start != $date_end) $date_string .= " al ".$date_end;
    
      return response()->json(new JsonResponse([
          'items' => $data, 
          'total' => sizeof($total), 
          'metrics' => $metrics, 
          'date' => $date_string, 
          'date_start' => $date_start,
          'date_end' => $date_end
          
        ]));

    }

    /**
     * 
     * Muestra la lista de productos en stock
     * 
    */ 

    public function get_full_list($date_start = null, $date_end = null, $inStock = null){
        
        /**
         * 
         * Si no recibe ninguna alguna fecha, coloca la de ayer por defecto 
         * 
        */

        $selector = '';

        if($inStock) {

            $selector = '=';

        }else{

            $selector = '>';
        }

        
        if(!$date_start || !$date_end){

            $datenow =  new DateTime('', new DateTimeZone('America/Santiago')); 
            $datenow->modify("-1 day");
     
            $date = $datenow->format("Y-m-d");

            $date_start = $date;
            $date_end = $date;

        }else{

        /**
         * 
         * Si recibe una fecha, la coloca en el formato correcto
         * 
        */

            $date_start_aux = explode("-", $date_start);
            $date_end_aux = explode("-", $date_end);

            if($date_start_aux[1] <10 ){

                $date_start = $date_start_aux[0]."-0".$date_start_aux[1]."-".$date_start_aux[2]; 
            }
            
            if($date_end_aux[1] <10 ){

                $date_end = $date_end_aux[0]."-0".$date_end_aux[1]."-".$date_end_aux[2]; 
            }


            //echo json_encode("Hora de inciio: ".$date_start." - Hora fin: ".$date_end);

        }

        /**
         * 
         * Comprobando si las fechas están en el orden correcto 
         * 
        */

        if($date_start > $date_end){

            $aux = $date_end;
            $date_end = $date_start;
            $date_start = $aux;

        }
        

        $rowsNumber = 10;
        $data = [];

        $total_product_views = 0;
        $total_product_sessions = 0;
        $products_stock = 0;

        $total_views_outs=0;
        $total_sessions_outs=0;
        $products_out_stock = 0;

        /**
         * 
         * Inicia el proceso de análisis de prodctos mediante a api de analytics y prestashop 
         * 
        */
        
        $products = Product::select(
                                    "product_url",  
                                    DB::raw("SUM(product_sessions) as total_sessions"),
                                    DB::raw("SUM(product_views) as total_views"),
                                    DB::raw("AVG(product_stock) as total_stock"),

                                )
                            ->where("analytics_date",'>=', $date_start)
                            ->where("analytics_date",'<=', $date_end)
                            ->where("product_stock",$selector, 0)
                            ->groupBy("product_url")
                            ->get();


        foreach($products as $product){

                $total_views_outs += $product->total_views;
                $total_sessions_outs += $product->total_sessions;
                $products_out_stock++;

                $row = [
                    'id' => $products_out_stock,
                    'views' => $product->total_views,
                    'sessions' => $product->total_sessions,
                    'product_url' => $product->product_url,

                ];
        
                array_push($data, $row);
           
        }
    
      return response()->json(new JsonResponse([
          'list' => $data, 
        ]));

    }

    public function test($date_start, $date_end){

        echo json_encode($date_start." - ".$date_end);
    }

    /**
     * 
     * Realiza el cálculo de las métricas generales
     * 
    */   

    public function metrics($date_start = null, $date_end = null){


        $data = [];

        $total_product_views = 0;
        $total_product_sessions = 0;
        $products_stock = 0;

        $total_views_outs=0;
        $total_sessions_outs=0;
        $products_out_stock = 0;

        $analytics_totals = $this->get_analytics_totals($date_start,  $date_end)->totalsForAllResults;

        $total_views = (int)$analytics_totals['ga:pageviews'];
        $total_sessions = (int)$analytics_totals['ga:sessions'];
        
        $products = Product::select('product_sessions',
                                    'product_views',
                                    'product_stock as stock'
                                )
                            ->where("analytics_date",'>=', $date_start)
                            ->where("analytics_date",'<=', $date_end)
                            ->get();

    

        foreach($products as $product){

            $total_product_views += $product->product_views;
            $total_product_sessions += $product->product_sessions;

            if($product->stock == 0){

                $total_views_outs += $product->product_views;
                $total_sessions_outs += $product->product_sessions;
                $products_out_stock++;

            }else{

                $products_stock++;

            }
           
        }

        if(sizeof($products)> 0){

            $data = [
                'total_views' => $total_views,
                'total_sessions' => $total_sessions,
                'total_product_views' => $total_product_views,
                'total_product_sessions' => $total_product_sessions,
                'total_views_outs' => $total_views_outs,
                'total_sessions_outs' => $total_sessions_outs,
                'products_out_stock' => $products_out_stock,
                'products_stock' => $products_stock,
                'total_products' => sizeof($products),
                'percent_product_stock' => round(($products_stock*100)/sizeof($products), 2),
                'percent_product_outs' => round(($products_out_stock*100)/sizeof($products), 2),
                'analytics_date' => $date_start
            ];

        }else{

            return null;
        }


        return $data;
        
    }

    /**
     * 
     * Historial del Stock de un producto
     * 
    */   

    public function history($product_url = null){

        $product_url = base64_decode($product_url);

        $products = Product::select('id',
                                    'product_url',
                                    'product_stock as stock',
                                    'analytics_date as date'
                                )
                            ->where("product_url",'=', $product_url)
                            ->orderBy('analytics_date', 'desc')
                            ->get();

    
        if($products){

            return response()->json(new JsonResponse(['history' => $products]));
        }

        return null;
        
    }

    /**
     * 
     * Método para paginar productos sin stock
     * 
    */   

    public function paginate($data = null){

        $params = json_decode($data);
        $data = [];

        
        $total_product_views = 0;
        $total_product_sessions = 0;
        $products_out_stock = 0;

        $page = (int)$params->page - 1;
        $limit = (int)$params->limit;
        $offset = $page*($limit);
        $date_start = $params->date_start_string;
        $date_end = $params->date_end_string;

        $products = Product::select(
                "product_url",  
                DB::raw("SUM(product_sessions) as total_sessions"),
                DB::raw("SUM(product_views) as total_views"),
                DB::raw("AVG(product_stock) as total_stock"),

            )
        ->where("analytics_date",'>=', $date_start)
        ->where("analytics_date",'<=', $date_end)
        ->where("product_stock",'=', 0)
        ->groupBy("product_url")
        ->offset($offset)
        ->limit($limit)
        ->get();

        if($products){

            foreach($products as $product){

                    $total_product_views += $product->total_views;
                    $total_product_sessions += $product->total_sessions;
                    $products_out_stock++;
    
                    $row = [
                        'id' => $products_out_stock + ($page*$limit),
                        'views' => $product->total_views,
                        'sessions' => $product->total_sessions,
                        'product_url' => $product->product_url,
    
                    ];
            
                    array_push($data, $row);
               
            }

            return response()->json(new JsonResponse(['list' => $data]));
        }


    }

    /**
     * 
     * Método para paginar productos en stock
     * 
    */   

    public function paginate_in_stock($data = null){

        $params = json_decode($data);
        $data = [];

        
        $total_product_views = 0;
        $total_product_sessions = 0;
        $products_out_stock = 0;

        $page = (int)$params->page - 1;
        $limit = (int)$params->limit;
        $offset = $page*($limit);
        $date_start = $params->date_start_string;
        $date_end = $params->date_end_string;

        $products = Product::select(
                "product_url",  
                DB::raw("SUM(product_sessions) as total_sessions"),
                DB::raw("SUM(product_views) as total_views"),
                DB::raw("AVG(product_stock) as total_stock"),

            )
        ->where("analytics_date",'>=', $date_start)
        ->where("analytics_date",'<=', $date_end)
        ->where("product_stock",'>', 0)
        ->groupBy("product_url")
        ->offset($offset)
        ->limit($limit)
        ->get();

        if($products){

            foreach($products as $product){

                    $total_product_views += $product->total_views;
                    $total_product_sessions += $product->total_sessions;
                    $products_out_stock++;
    
                    $row = [
                        'id' => $products_out_stock + ($page*$limit),
                        'views' => $product->total_views,
                        'sessions' => $product->total_sessions,
                        'product_url' => $product->product_url,
    
                    ];
            
                    array_push($data, $row);
               
            }

            return response()->json(new JsonResponse(['list' => $data]));
        }


    }

}