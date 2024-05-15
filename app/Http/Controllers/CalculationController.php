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
        $cabinateInSquarInch = (2*($cabinateSize['h']+$cabinateSize['d']))+($cabinateSize['h']+$cabinateSize['w'])+ (2*($cabinateSize['w']+$cabinateSize['d']));
        
        $singleShelveSquar= round($cabinateSize['w']*$cabinateSize['d']);
       
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
                    'totalShelvs'=>$extractedItems['Shelves'],
                ],          
            'door'=>$this->doorCalc($cabinateSize,$cabinetInteriorMaterialPrice,$extractedItems['Door']),
            'drawer'=>$this->drawerCalc($cabinateSize,$cabinetInteriorMaterialPrice,$extractedItems['Drawer']),

        ]);
    }

    private function cmToInches(Request $request)
    {

        $dimensionUnit = (int) $request->input('dimension_unit_data',0);
        $dimensionWidth = (int) $request->input('dimension_width_data',0);
        $dimensionWidthFrac = (int) $request->input('dimension_width_data_frac',0);
        $dimensionHeight = (int) $request->input('dimension_height_data',0);
        $dimensionHeightFrac = (int) $request->input('dimension_height_data_frac',0);
        $dimensionDept = (int) $request->input('dimension_dept_data',0);
        $dimensionDeptFrac = (int) $request->input('dimension_dept_data_frac',0);
        
        if ($dimensionUnit === 'centimeter') {
            $dimensionWidthInInches = ($dimensionWidth+(1/$dimensionWidthFrac)) * 0.393701;
            $dimensionHeightInInches = ($dimensionHeight+(1/$dimensionHeightFrac)) * 0.393701;
            $dimensionDeptInInches = ($dimensionDept+(1/$dimensionDeptFrac)) * 0.393701;
            
          
        } else {
            $dimensionWidthInInches = ($dimensionWidth+(1/$dimensionWidthFrac));
            $dimensionHeightInInches = ($dimensionHeight+(1/$dimensionHeightFrac));
            $dimensionDeptInInches = ($dimensionDept+(1/$dimensionDeptFrac));
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

    private function doorCalc($size,$price,$count):array{
              return [
                        'singleDoorSquarInch'=>$size['w']*$size['d']*$size['h'],
                        'singleDoorPrice'=>round($size['w']*$size['d']*$size['h']*$price),
                        'totalDoor'=> $count
                    ];
    }
    private function drawerCalc($size,$price,$count):array{
              return [
                        'singleDoorSquarInch'=>$size['w']*$size['d']*$size['h'],
                        'singleDoorPrice'=>round($size['w']*$size['d']*$size['h']*$price),
                        'totalDoor'=> $count
                    ];
    }
}
