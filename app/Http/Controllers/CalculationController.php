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
        $cabinetBoxConstruction = $request->input('data_product_specification.value');
        $cabinetAttachItem = $request->input('cabinate_type_data.data_product_specification');
        $cabinetDoorStyle = $request->input('cabinate_door_style_data.value');
        $cabinetInteriorMaterialPrice = $request->input('cabinet_interior_material_data.value');   
        $cabinateSize=$this->cmToInches($request);
        $extractedItems = $this->extractAttachItem($cabinetAttachItem);
        $cabinateInSquarInch = (2*($cabinateSize['h']*$cabinateSize['d']))+($cabinateSize['h']*$cabinateSize['w'])+ (2*($cabinateSize['w']*$cabinateSize['d']));
        
        $singleShelveSquar= round($cabinateSize['w']*$cabinateSize['d']);
        $drawarCalc=$this->drawerCalc($cabinateSize,$cabinetInteriorMaterialPrice,$extractedItems['Drawer']??0);
        $doorCalc= $this->doorCalc($drawarCalc['totalDrawerHeight'],$cabinateSize,$cabinetInteriorMaterialPrice,$extractedItems['Door']??0);
        return response()->json([
            'cabinet_box_construction' => $cabinetBoxConstruction,
            'cabinet_attach_item' =>$extractedItems,
            'cabinet_door_style' => $cabinetDoorStyle,
            'cmToInche'=>$cabinateSize,
            'cabinateInSquareInch'=>$cabinateInSquarInch,
            'manufacturingCostDoller'=>round($cabinateInSquarInch*$this->manufaturingCost,2),
            'cabinateBoxPriceDollar'=>round($cabinateInSquarInch*$cabinetInteriorMaterialPrice),
            
            'shelv'=>[
                    'singleShelvsSquarInch'=>$singleShelveSquar,
                    'singleShelvsPrice'=>round($singleShelveSquar*$cabinetInteriorMaterialPrice),
                    'totalShelvs'=>$extractedItems['Shelves']??0,
                ],          
            'door'=>$doorCalc,
            'drawer'=>$drawarCalc,
            'totalPrice'=>round($cabinateInSquarInch*$cabinetInteriorMaterialPrice)+(round($singleShelveSquar*$cabinetInteriorMaterialPrice)*($extractedItems['Shelves']??0))+($doorCalc['singleDoorPrice']*$doorCalc['totalDoor'])+($drawarCalc['singleDrawerPrice']*$drawarCalc['totalDrawer'])+round($cabinateInSquarInch*$this->manufaturingCost,2)

        ]);
    }

    private function cmToInches(Request $request)
    {

        $dimensionUnit =  $this->numberCheck($request->input('dimension_unit_data'));
        $dimensionWidth = $this->numberCheck($request->input('dimension_width_data'));
        $dimensionWidthFrac =  $this->numberCheck($request->input('dimension_width_data_frac'),true);
        $dimensionHeight = $this->numberCheck($request->input('dimension_height_data'));
        $dimensionHeightFrac =  $this->numberCheck($request->input('dimension_height_data_frac'),true);
        $dimensionDept =  $this->numberCheck($request->input('dimension_dept_data'));
        $dimensionDeptFrac =  $this->numberCheck($request->input('dimension_dept_data_frac'),true);
        
        if ($dimensionUnit === 'centimeter') {
            $dimensionWidthInInches = ($dimensionWidth+$dimensionWidthFrac) * 0.393701;
            $dimensionHeightInInches = ($dimensionHeight+$dimensionHeightFrac) * 0.393701;
            $dimensionDeptInInches = ($dimensionDept+$dimensionDeptFrac) * 0.393701;
            
          
        } else {
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
                'S' => 'Shelves',
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
        $number = intval($number);
        if($isFraction){
            return $number<=0?0:$number/10;         
        }else{
             return $number<=0?0:$number; 
        }
    }
}
