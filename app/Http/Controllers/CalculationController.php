<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class CalculationController extends Controller
{ 

   public $manufaturingCost;

    public function __construct() {
        $this->manufaturingCost =  0.01;
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
        $doorPrice = $request->input('cabinate_door_style_data.value');
        $drawarCalc=$this->drawerCalc($cabinateSize,$doorPrice,$extractedItems['Drawer']??0);
        $doorCalc= $this->doorCalc($drawarCalc['totalDrawerHeight'],$cabinateSize,$doorPrice,$extractedItems['Door']??0);
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
        $pullout_shelves_single=$pullout_shelves_price*$cabinateSize['d'] * $cabinateSize['w'];
        $pullout_shelve_calc = [
            'pullout_shelve_single_square_inch'=> $cabinateSize['d'] * $cabinateSize['w'],
            'pullout_shelve_single_price'=> round($pullout_shelves_single,2),
            'pullout_shelve_qty'=> $number_of_pullout_shelves,
            'pullout_shelve_total_price'=> round($pullout_shelves_single*$number_of_pullout_shelves,2)
        ];
        //fished side
        $finished_side_data = $request->input('finished_side_data.data_product_name');
        $finishes_side_type = $request->input('finishes_side_type');
        $sideCount = $this->containsWord($finished_side_data,'Both')?2:1;
        $finishes_side_price = 0 ;
        if($this->containsWord($finishes_side_type,'Door')){
            $finishes_side_price = $doorPrice;
        }else{
            $finishes_side_price = $cabinetInteriorMaterialPrice;
        }
        $finished_side =[
            'price'=>round($sideCount*$finishes_side_price*($cabinateSize['h']) * ($cabinateSize['w']),2),
            'qty'=> $sideCount,
        ];
        // dd($shelveCalc);
        $cabinateInSquarInch = (2*($cabinateSize['h']*$cabinateSize['d']))+($cabinateSize['h']*$cabinateSize['w'])+ (2*($cabinateSize['w']*$cabinateSize['d']));
        $allPrice = round($cabinateInSquarInch*$cabinetInteriorMaterialPrice)+(round($finished_side['price']))+
        (round($pullout_shelve_calc['pullout_shelve_total_price']))+(round($fixed_shelves_calc['fixed_shelve_total_price']))+($doorCalc['singleDoorPrice']*$doorCalc['totalDoor'])+($drawarCalc['singleDrawerPrice']*$drawarCalc['totalDrawer'])+round($cabinateInSquarInch*$this->manufaturingCost,2);
        
        return response()->json([
            'cabinet_interior_material_price'=>$cabinetInteriorMaterialPrice,
            'door_material_price'=>$doorPrice,
            'cabinet_attach_item' =>$extractedItems,
            'cmToInche'=>$cabinateSize,
            'cabinateInSquareInch'=>round($cabinateInSquarInch,2),
            'manufacturingCostDoller'=>round($cabinateInSquarInch*$this->manufaturingCost,2),
            'cabinateBoxPriceDollar'=>round($cabinateInSquarInch*$cabinetInteriorMaterialPrice),
            'fixed_shelves_calc'=>$fixed_shelves_calc,     
            'pullout_shelve_calc'=>$pullout_shelve_calc, 
            'finished_side'=>$finished_side,    
            'door'=>$doorCalc,
            'drawer'=>$drawarCalc,
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
              return [
                        'singleDoorSquarInch'=>$size['w']*($size['h']-$drawar_height),
                        'singleDoorPrice'=>round(($size['w']*($size['h']-$drawar_height))*$price),
                        'totalDoor'=> $count
                    ];
    }
    private function drawerCalc($size,$price,$count):array{

            $singleDrawarHeight = $size['h']/4;

              return [
                        'singleDrawerSquarInch'=>$size['w']*$singleDrawarHeight,
                        'singleDrawerPrice'=>round($size['w']*$singleDrawarHeight*$price),
                        'totalDrawer'=> $count,
                        'totalDrawerHeight'=>$singleDrawarHeight*$count
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
         // Extracting data from the request
         $cabinetBoxConstruction = $request->input('data_product_specification.value');
         $cabinetAttachItem = $request->input('cabinate_type_data.data_product_specification');
         $cabinetDoorStyle = $request->input('cabinate_door_style_data.value');
         $cabinetInteriorMaterialPrice = $request->input('cabinet_interior_material_data.value');   
         $cabinateSize=$this->cmToInches($request);
         $extractedItems = $this->extractAttachItem($cabinetAttachItem);
         $cabinateInSquarInch = (2*($cabinateSize['h']*$cabinateSize['d']))+($cabinateSize['h']*$cabinateSize['w'])+ (2*($cabinateSize['w']*$cabinateSize['d']));
         $quantity = $request->quantity;
 
         $singleShelveSquar= round($cabinateSize['w']*$cabinateSize['d']);
         $drawarCalc=$this->drawerCalc($cabinateSize,$cabinetInteriorMaterialPrice,$extractedItems['Drawer']??0);
         return response()->json([
             'cabinet_box_construction' => $cabinetBoxConstruction,
             'cabinet_attach_item' =>$extractedItems,
             'cabinet_door_style' => $cabinetDoorStyle,
             'cmToInche'=>$cabinateSize,
             'cabinateInSquareInch'=>$cabinateInSquarInch,
             'manufacturingCostDoller'=>round($cabinateInSquarInch*$this->manufaturingCost,2),
             'cabinateBoxPriceDollar'=>round($cabinateInSquarInch*$cabinetInteriorMaterialPrice),
             'drawer'=>$drawarCalc,
             'totalPrice'=>(round($cabinateInSquarInch*$cabinetInteriorMaterialPrice)+(round($singleShelveSquar*$cabinetInteriorMaterialPrice)*($extractedItems['Shelves']??0))+($drawarCalc['singleDrawerPrice']*$drawarCalc['totalDrawer'])+round($cabinateInSquarInch*$this->manufaturingCost,2)) * $quantity
 
         ]);

    }

    
}
