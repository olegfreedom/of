<?php
namespace Application\Controller;

class CatalogController extends \Application\Base\Controller
{
    public function __construct()
    {
        parent::__construct();

        $this->pushTitle('Главная');
    }

    public function indexAction($params = array())
    {
        $this->log(__CLASS__ . '\\' . __FUNCTION__);

        $page = $this->p_int('page', 1);
        $category = $this->p_int('category');

        $Model = $this->load('Adverts', 'admin');
        $CategoryModel = $this->load('AdvertCategory', 'admin');

        $categoriesList = $CategoryModel->get();

        $params = array(
            'page' => $page,
            'type' => $Model::TYPE_ALL,
        );

        $currentCategory = array();
        if ( !empty($category) )
        {
            $params ['category'] = $category;
            $currentCategory = $CategoryModel->getBreadcrumbsArray($category);
        }

        $advertsList = $Model->getList($params);


        $ret = array(
            'Model' => $Model,
            'advertsList' => $advertsList,
            'categoriesList' => $categoriesList,
            'currentCategory' => $currentCategory,
        );

        return $this->view($ret);
/*
        $search_text = $this->p_string('search_text');
        $page = $this->p_int('page', 1);
        $advertsType = $this->p_int('type', $this->load('AdvertType', 'admin')->getDefaultTypeId());
        $params['page'] = $page;
        $params['type'] = $advertsType;
        $params['price_min'] = $this->p_int('price_min');
        $params['price_max'] = $this->p_int('price_max');
        $params['location'] = $this->p_int('location');
        $params['region'] = $this->p_int('region');
        $params['category'] = $this->p_int('category');
        $params['image'] = $this->p_select('image', 'n', array('n', 'y'));
        $params['search_text'] = $search_text;
        

        if ($this->p_int('search-form') === 1) {
            $search_text = trim($search_text);
            if (!empty($search_text)) {
                $this->load('SearchLog', 'admin')->add($search_text);
            }
            return $this->redirect()->toUrl($this->easyUrl($this->urlParams($params), $this->urlQuery($params)));
        }
        $category = isset($params['category']) ? $params['category'] : 0;
        $ret = array(
            'searchText' => $search_text,
            'currentCategory' => $category,
            'categoryList' => $this->load('AdvertCategory', 'admin')->get(),
            'advertList' => $this->load('Adverts')->getList($params),
            'topList' => $this->load('Adverts')->getList($params, true),
            'locationList' => $this->load('AdvertLocation', 'admin')->getRegions(),
            'paginator' => $this->load('Adverts')->getPaginator($params),
            'typeList' => $this->load('AdvertType', 'admin')->get(),
            'advertsType' => $advertsType,
            'params' => $params,
            'maxPrice' => $this->load('Adverts')->getMaxPrice(),
            'bannersList' => $this->load('Banners', 'admin')->getList(),
            'advertsCount' => $this->load('Adverts')->getListCount($params)
        );
        return $this->view($ret, 'application/catalog/index.phtml');
*/
    }

    public function searchAction()
    {
        $this->log(__CLASS__ . '\\' . __FUNCTION__);

        $params = array(
            'category' => $this->p_int('category')
        );

        return $this->indexAction($params);
    }

    public function searchValidatorAction()
    {
        $this->log(__CLASS__ . '\\' . __FUNCTION__);
        $this->isAjax();

        $params = array(
            'search_text' => $this->p_string('search_text'),
        );

        $error = array();

        $validItem = $this->load('Validator')->validStringLength($params['search_text'], 0, 100);
        if ($validItem == false) {
            $error['search_text'] = $validItem;
        }

        $ret = array(
            'status' => (sizeof($error) > 0 ? false : true),
            'error' => $error
        );
        return $this->json($ret);
    }

    public function getCityAction(){
        $this->log(__CLASS__ . '\\' . __FUNCTION__);
        $this->isAjax();

        $region_id = $this->p_int('region');

        $ret = array(
            'citiesList' => $this->load('AdvertLocation', 'admin')->getCitiesByRegion($region_id)
        );

        return $this->json($ret);
    }

    /**
     * filtered return parameters
     * @param array $params
     * @return array
     */
    private function urlParams($params)
    {
        $this->log(__CLASS__ . '\\' . __FUNCTION__);

        if (isset($params) && !empty($params)) {
            foreach ($params as $key => $item) {
                if (empty($item) || $item === 'n') {
                    unset($params[$key]);
                }
            }

            if (isset($params['search_text'])) {
                unset($params['search_text']);
            }
            if (isset($params['page'])) {
                unset($params['page']);
            }
        }

        $params['controller'] = 'catalog';
        $params['action'] = 'search';
        return $params;
    }

    /**
     * return query param
     * @param array $params
     * @return array
     */
    private function urlQuery($params)
    {
        $this->log(__CLASS__ . '\\' . __FUNCTION__);

        $query = array();

        if (isset($params['search_text']) && !empty($params['search_text'])) {
            $query['search_text'] = urlencode($params['search_text']);
        }

        return $query;
    }
}
