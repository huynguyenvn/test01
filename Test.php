<?php

class Travel extends Base
{
    private $_companyPrices = [];
    public function __construct() {
        parent::__construct('https://5f27781bf5d27e001612e057.mockapi.io/webprovise/travels');
        $this->_processData();
    }

    private function _processData() {
        try {
            $companyPrices = [];
            foreach ($this->data as $key => $travel) {
                $companyPrices[$travel['companyId']] = isset($companyPrices[$travel['companyId']]) ? $companyPrices[$travel['companyId']] + floatval($travel['price']) : floatval($travel['price']);
            }

            $this->_companyPrices = $companyPrices;
        } catch (Exception $e) {
            echo 'Error process travel price: ' . $e->getMessage();
            return [];
        }
    }

    public function getTotalCostByCompanyId($companyId) {
        return $this->_companyPrices[$companyId] ?? 0;
    }
}

class Company extends Base
{
    private $_travel;
    public function __construct() {
        parent::__construct('https://5f27781bf5d27e001612e057.mockapi.io/webprovise/companies');
        $this->_travel = new Travel();
    }

    private function _buildTree($companies, $parentId = 0) {
        $trees = [];
        $companyCost = 0;
        $tempCompanies = $companies;
        foreach ($tempCompanies as $key => $company) {
            if ($company['parentId'] == $parentId) {
                unset($companies[$key]);
                list($children, $cost, $companies) = $this->_buildTree($companies, $company['id']);
                $companyCost = $this->_travel->getTotalCostByCompanyId($company['id']) + $cost;
                $trees[] = [
                    'id' => $company['id'],
                    'name' => $company['name'],
                    'cost' => $companyCost,
                    'children' => $children,
                ];
            }
        }

        return [$trees, $companyCost, $companies];
    }

    public function showTree() {
        list($trees) = $this->_buildTree($this->data);

        return $trees;
    }
}

class TestScript
{
    public function execute()
    {
        $start = microtime(true);
        $a = new Company();
        $tree = $a->showTree();
        echo json_encode($tree);
        echo 'Total time: '.  (microtime(true) - $start);
    }
}

class Base {
    private $_dataApiUrl;
    protected $data;

    public function __construct($dataApiUrl) {
        $this->_dataApiUrl = $dataApiUrl;
        $this->data = $this->_getData();
    }


    private function _getData() {
        try {
            if (is_null($this->_dataApiUrl)) {
                return [];
            }

            if (!$response = file_get_contents($this->_dataApiUrl)) {
                return [];
            }

            return json_decode($response, true);

        } catch (Exception $e) {
            echo 'Error get data: ' . $e->getMessage();
            return [];
        }
    }
}

(new TestScript())->execute();
