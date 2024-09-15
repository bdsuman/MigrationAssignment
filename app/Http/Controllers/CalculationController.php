<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class CalculationController extends Controller
{ 

   public $manufaturingCost;
   public $pullout_shelves_height;
   public $pullout_shelves_manufracring_cost;
   public $door_manufacturing_fixed_cost;
   public $drawar_manufacturing_fixed_cost;
    public function __construct() {
        $this->manufaturingCost =  0.01;
        $this->pullout_shelves_height = 5;
        $this->pullout_shelves_manufracring_cost = 79.49;
        $this->door_manufacturing_fixed_cost = 17.80;
        $this->drawar_manufacturing_fixed_cost = 79.49;
    }

    public function index(Request $request)
    {
        // Extracting data from the request
        $cabinetAttachItem = $request->input('cabinate_type_data.data_product_specification');
        $extractedItems = $this->extractAttachItem($cabinetAttachItem);
        $cabinateSize=$this->cmToInches($request);
        $quantity = $request->input('quantity',1);
        //door calculation
        $cabinetInteriorMaterialPrice = $request->input('cabinet_interior_material_data.value');
        $doorPrice = $request->input('door_drawer_front_color_data.value');
        $doorMDFPrice = $request->input('cabinate_door_style_data.value');
        $door_product_parent = $request->input('door_drawer_front_color_data.data_product_parent');
        $drawar_qty = $extractedItems['Drawer']??0; 
        $door_qty = $extractedItems['Door']??0;
        $drawarCalc=$this->drawerCalc($cabinateSize,$doorPrice,$door_qty,$drawar_qty);

        $drawarCalcTwo=[
            'singleDrawerSquarInch'=>0,
            'singleDrawerPrice'=>0,
            'drawar_manufacturing_fixed_cost'=> 0
        ];
        if($drawar_qty==2){
            $drawarCalcTwo=$this->drawerCalc($cabinateSize,$doorPrice,$door_qty,$drawar_qty);
        }

        $drawarCalcThree=[
            'singleDrawerSquarInch'=>0,
            'singleDrawerPrice'=>0,
            'drawar_manufacturing_fixed_cost'=> 0
        ];
        if($drawar_qty==3){
            $drawarCalcThree=$this->drawerCalc($cabinateSize,$doorPrice,$door_qty,$drawar_qty);
        }

        $drawarCalcFour=[
            'singleDrawerSquarInch'=>0,
            'singleDrawerPrice'=>0,
            'drawar_manufacturing_fixed_cost'=> 0
        ];
        if($drawar_qty==4){
            $drawarCalcFour=$this->drawerCalc($cabinateSize,$doorPrice,$door_qty,$drawar_qty);
        }


        $doorCalc= $this->doorCalc(6*$drawar_qty,$cabinateSize,$doorPrice,$door_qty);
        $doorTwoCalc=[
            'price_of_unit'=>0,
            'singleDoorSquarInch'=>0,
            'door_manufacturing_fixed_cost'=>0,
            'singleDoorPrice'=>0,
        ];
        if($door_qty==2){
            $doorTwoCalc= $this->doorCalc(6*$drawar_qty,$cabinateSize,$doorPrice,$door_qty);
        }
        $number_of_fixed_shelves = $request->input('number_of_fixed_shelves')??0; 
        $number_of_fixed_shelves_type = $request->input('number_of_fixed_shelves_type'); 
        //fixed shelves calculation
        $fixed_shelves_price = 0 ;
        if($this->containsWord($number_of_fixed_shelves_type,'Door')){
            $fixed_shelves_price = $doorPrice;
        }else{
            $fixed_shelves_price = $cabinetInteriorMaterialPrice;
        }
        $fixed_shelves_single=$fixed_shelves_price*$cabinateSize['d'] * $cabinateSize['w'];
        $fixed_shelves_calc = [
            'fixed_shelve_single_square_inch'=> $cabinateSize['d'] * $cabinateSize['w'],
            'fixed_shelve_single_price'=> round($fixed_shelves_single,2),
            'fixed_shelve_qty'=> $number_of_fixed_shelves,
            'fixed_shelve_total_price'=> round($fixed_shelves_single* $number_of_fixed_shelves,2)
        ];

        //pullout calc
        $number_of_pullout_shelves = $request->input('number_of_pullout_shelves')??0; 
        $number_of_pullout_shelves_type = $request->input('number_of_pullout_shelves_type'); 
        
        $pullout_shelves_price = 0 ;
        // dd($this->containsWord($number_of_pullout_shelves_type,'Door'));
        if($this->containsWord($number_of_pullout_shelves_type,'Door')){
            $pullout_shelves_price = $doorPrice;
        }else{
            $pullout_shelves_price = $cabinetInteriorMaterialPrice;
        }
        $pullout_shelves_single=$pullout_shelves_price*$this->pullout_shelves_height*$cabinateSize['w'];
        $pullout_shelves_box = (2*$this->pullout_shelves_height*$cabinateSize['d'])+($cabinateSize['w']*$cabinateSize['d'])+(2*$cabinateSize['w']*$this->pullout_shelves_height);
        $pullout_shelve_calc = [
            'pullout_shelve_single_square_inch'=> $this->pullout_shelves_height*$cabinateSize['w'],
            'pullout_shelves_box_square_inch'=>$pullout_shelves_box,
            'pullout_shelve_single_price'=> round(round($pullout_shelves_single,2)+round($pullout_shelves_box*$pullout_shelves_price,2)+$this->pullout_shelves_manufracring_cost,2),
            'pullout_shelve_qty'=> $number_of_pullout_shelves,
            'pullout_shelve_total_price'=> round($number_of_pullout_shelves*(round($pullout_shelves_single,2)+round($pullout_shelves_box*$pullout_shelves_price,2)+$this->pullout_shelves_manufracring_cost),2)
        ];
        //fished side
        $finished_side_data = $request->input('finished_side_data.data_product_name');
        $finishes_side_type = $request->input('finishes_side_type');
        $sideCount = empty($finished_side_data)?0:($this->containsWord($finished_side_data, 'Both') ? 2 : 1);

        $finishes_side_price = 0 ;
        if($this->containsWord($finishes_side_type,'Door')){
            $finishes_side_price = $doorPrice;
        }else{
            $finishes_side_price = $cabinetInteriorMaterialPrice;
        }
        $squar_finished_side = $cabinateSize['h']*$cabinateSize['d'];
        $finished_side_manufaturing_cost = round($cabinateSize['h']*$cabinateSize['w']*$this->manufaturingCost,2)*$sideCount;
        $finished_side =[
            'single_squar_inch'=>$squar_finished_side,
            'qty'=> $sideCount,
            'single_price'=>round($finishes_side_price*$squar_finished_side),
            'manufractur_cost'=> $finished_side_manufaturing_cost,
            'price'=>round($sideCount*$finishes_side_price*$squar_finished_side,2)+$finished_side_manufaturing_cost,
        ];
        // dd($shelveCalc);
        $cabinateInSquarInch = (2*($cabinateSize['h']*$cabinateSize['d']))+($cabinateSize['h']*$cabinateSize['w'])+ (2*($cabinateSize['w']*$cabinateSize['d']));
       
        //only mdf door calculation 
        $mdf_door =  [
            'squar_inche'=>0,
            'price'=>0,
            'total_price'=>0
        ];

        if($this->containsWord($door_product_parent,'MDF')){
            $mdf_door = [
                        'squar_inche'=>($cabinateSize['h']-(6*$drawar_qty))*$cabinateSize['w'],
                        'price'=>$doorMDFPrice,
                        'total_price'=>round(($cabinateSize['h']-(6*$drawar_qty))*$cabinateSize['w']*$doorMDFPrice,2)
                    ];
        }

        $allPrice = round(
                    round($mdf_door['total_price'])+
                    round($cabinateInSquarInch*$cabinetInteriorMaterialPrice)+
                    (round($finished_side['price']))+
                    (round($pullout_shelve_calc['pullout_shelve_total_price']))+
                    (round($fixed_shelves_calc['fixed_shelve_total_price']))+
                    ($doorCalc['singleDoorPrice']+$doorTwoCalc['singleDoorPrice'])+
                    ($drawarCalc['singleDrawerPrice']*$drawar_qty)+
                    round($cabinateInSquarInch*$this->manufaturingCost,2),2);
        

        return response()->json([
            'cabinet_interior_material_price'=>$cabinetInteriorMaterialPrice,
            'door_material_price'=>$doorPrice,
            'cabinet_attach_item' =>$extractedItems,
            'cmToInche'=>$cabinateSize,
            'cabinateInSquareInch'=>round($cabinateInSquarInch,2),
            'manufacturingCostDoller'=>round($cabinateInSquarInch*$this->manufaturingCost,2),
            'cabinateBoxPriceDollar'=>round($cabinateInSquarInch*$cabinetInteriorMaterialPrice,2),
            'fixed_shelves_calc'=>$fixed_shelves_calc,     
            'pullout_shelve_calc'=>$pullout_shelve_calc, 
            'finished_side'=>$finished_side,    
            'DoorCost'=>['one'=>$doorCalc,'two'=>$doorTwoCalc,'quantity'=>$door_qty],
            'drawer'=>['one'=>$drawarCalc,'two'=>$drawarCalcTwo,'three'=>$drawarCalcThree,'four'=>$drawarCalcFour,'quantity'=>$drawar_qty],
            'MDFDoorCost'=>$mdf_door,
            'totalPrice'=>$allPrice*$quantity
        ]);
    }

    private function cmToInches(Request $request)
    {

        $dimensionUnit =  $this->numberCheck($request->input('dimension_unit_data'));

        if ($dimensionUnit === 'centimeter') {
            /**
            *"cm_width": "36",
            *"cm_height": "36",
            *"cm_dept": "37",
            *"cm_width_fraction": "0.4",
            *"cm_height_fraction": "0.9",
            *"cm_dept_fraction": "0.9"
             */
            $dimensionWidthFrac =  $this->numberCheck($request->input('cm_width_fraction',0.6));
            $dimensionWidth = $this->numberCheck($request->input('cm_width',41));
            $dimensionHeightFrac =  $this->numberCheck($request->input('cm_height_fraction',0.6));
            $dimensionHeight = $this->numberCheck($request->input('cm_height',88));
            $dimensionDeptFrac =  $this->numberCheck($request->input('cm_dept_fraction',0.6));
            $dimensionDept =  $this->numberCheck($request->input('cm_dept',60));

            $dimensionWidthInInches = ($dimensionWidth+$dimensionWidthFrac) * 0.393701;
            $dimensionHeightInInches = ($dimensionHeight+$dimensionHeightFrac) * 0.393701;
            $dimensionDeptInInches = ($dimensionDept+$dimensionDeptFrac) * 0.393701;
            
        } else {
            /**
             *"inch_width": "36",
             *"inch_height": "39",
             *"inch_dept": "38",
             *"inch_width_fraction": "1/8",
            *"inch_height_fraction": "1/2",
            *"inch_dept_fraction": "3/8"
             */
            $dimensionWidth = $this->numberCheck($request->input('inch_width',16));
            $dimensionWidthFrac =  $this->numberCheck($request->input('inch_width_fraction',0),true);
            $dimensionHeight = $this->numberCheck($request->input('inch_height',34));
            $dimensionHeightFrac =  $this->numberCheck($request->input('inch_height_fraction',0),true);
            $dimensionDept =  $this->numberCheck($request->input('inch_dept',23));
            $dimensionDeptFrac =  $this->numberCheck($request->input('inch_dept_fraction',0),true);

            $dimensionWidthInInches = ($dimensionWidth+$dimensionWidthFrac);
            $dimensionHeightInInches = ($dimensionHeight+$dimensionHeightFrac);
            $dimensionDeptInInches = ($dimensionDept+$dimensionDeptFrac);
        }

          // Returning converted dimensions
          return[
                'w' => round($dimensionWidthInInches,2),
                'h' => round($dimensionHeightInInches,2),
                'd' => round($dimensionDeptInInches,2),
            ];
    }

    private function extractAttachItem($input) : array {

            $mappings = [
                'D' => 'Door',
                'DR' => 'Drawer',
            ];

            $pattern = '/(\d+)([A-Z]+)/';
            $extracted = [];
            preg_match_all($pattern, $input, $matches, PREG_SET_ORDER);

        foreach ($matches as $match) {
            $key = $mappings[$match[2]];
            $digit = $match[1]; 
            $extracted[$key] = intval($digit);
        }
        return $extracted;
    }

    private function doorCalc($drawar_height,$size,$price,$count):array{

            if($count==0){
                return [    
                    'price_of_unit'=>0,
                    'singleDoorSquarInch'=>0,
                    'door_manufacturing_fixed_cost'=>0,
                    'singleDoorPrice'=>0,
                ];
            }
            return [    
                        'price_of_unit'=>$price,
                        'singleDoorSquarInch'=>($size['w']/$count)*($size['h']-$drawar_height),
                        'door_manufacturing_fixed_cost'=>$this->door_manufacturing_fixed_cost,
                        'singleDoorPrice'=>round(round((($size['w']/$count)*($size['h']-$drawar_height))*$price,2)+$this->door_manufacturing_fixed_cost,2),
                    ];
    }
    private function drawerCalc($size,$price,$door_qty,$drawar_qty):array{
        if($door_qty>0){
            $singleDrawarHeight=6;
            $boxSingleDrawarHeight=6;
        }else{
            $box_drawar_qty=$drawar_qty==1?2:$drawar_qty;
            $singleDrawarHeight=$size['h']/$drawar_qty;
            $boxSingleDrawarHeight = $size['h']/$box_drawar_qty;
        }

            $box_area = (2*$boxSingleDrawarHeight*$size['d'])+($size['d']*$size['w'])+(2*$boxSingleDrawarHeight*$size['w']);
              return [
                        'price'=>$price,
                        'singleDrawerSquarInch'=>$size['w']*$singleDrawarHeight,
                        'singleDrawerPrice'=>round($size['w']*$singleDrawarHeight*$price+$this->drawar_manufacturing_fixed_cost+($box_area*$price),2),
                        'drawar_manufacturing_fixed_cost'=> $this->drawar_manufacturing_fixed_cost,
                        'box_cost'=>[
                            'area'=>$box_area,
                            'price'=>round($box_area*$price,2)
                        ]
                    ];
    }

    private function numberCheck($number,$isFraction=false){
    
        if($isFraction){
            return $number<=0?0:$this->parseFractionValue($number);         
        }else{
             return $number<=0?0:$number; 
        }
    }

    private function containsWord($str, $word) {
        return strpos($str, $word) !== false;
    }

    private function parseFractionValue($fraction) {

        if($fraction==0){
            return 0;
        }
        
        list($numerator, $denominator) = explode('/', $fraction);
        return (int)$numerator/(int)$denominator;
    }

   


  public function vanities(Request $request){
        //  // Extracting data from the request
        //  $cabinetBoxConstruction = $request->input('data_product_specification.value');
        //  $cabinetAttachItem = $request->input('cabinate_type_data.data_product_specification');
        //  $cabinetDoorStyle = $request->input('cabinate_door_style_data.value');
        //  $cabinetInteriorMaterialPrice = $request->input('cabinet_interior_material_data.value');   
        //  $cabinateSize=$this->cmToInches($request);
        //  $extractedItems = $this->extractAttachItem($cabinetAttachItem);
        //  $cabinateInSquarInch = (2*($cabinateSize['h']*$cabinateSize['d']))+($cabinateSize['h']*$cabinateSize['w'])+ (2*($cabinateSize['w']*$cabinateSize['d']));
        //  $quantity = $request->quantity;
 
        //  $singleShelveSquar= round($cabinateSize['w']*$cabinateSize['d']);
        //  $drawarCalc=$this->drawerCalc($cabinateSize,$cabinetInteriorMaterialPrice,$extractedItems['Drawer']??0);
        //  return response()->json([
        //      'cabinet_box_construction' => $cabinetBoxConstruction,
        //      'cabinet_attach_item' =>$extractedItems,
        //      'cabinet_door_style' => $cabinetDoorStyle,
        //      'cmToInche'=>$cabinateSize,
        //      'cabinateInSquareInch'=>$cabinateInSquarInch,
        //      'manufacturingCostDoller'=>round($cabinateInSquarInch*$this->manufaturingCost,2),
        //      'cabinateBoxPriceDollar'=>round($cabinateInSquarInch*$cabinetInteriorMaterialPrice),
        //      'drawer'=>$drawarCalc,
        //      'totalPrice'=>(round($cabinateInSquarInch*$cabinetInteriorMaterialPrice)+(round($singleShelveSquar*$cabinetInteriorMaterialPrice)*($extractedItems['Shelves']??0))+($drawarCalc['singleDrawerPrice']*$drawarCalc['totalDrawer'])+round($cabinateInSquarInch*$this->manufaturingCost,2)) * $quantity
 
        //  ]);

    }

    
}
