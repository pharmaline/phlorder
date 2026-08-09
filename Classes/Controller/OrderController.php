<?php
namespace Pharmaline\Phlorder\Controller;

use Pharmaline\Phlorder\Domain\Model\Order;
use Pharmaline\Phlorder\Domain\Repository\OrderRepository;
use Pharmaline\Phlusereditor\Domain\Model\Phluser;
use Pharmaline\Phlusereditor\Domain\Repository\PhluserRepository;
use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Type\ContextualFeedbackSeverity;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use TYPO3\CMS\Extbase\Persistence\QueryResultInterface;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;

/***
 *
 * This file is part of the "phlorder Bestellung" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 *  (c) 2018 Christian Platt <christian.platt@pharmaline.de>, pharmaline
 *
 ***/

/**
 * OrderController
 */
class OrderController extends \TYPO3\CMS\Extbase\Mvc\Controller\ActionController
{
	/** aktuell aufgeloester Phluser (Apotheke) - null, wenn keiner ermittelbar */
	protected ?Phluser $phluser = null;

	/** Treffer der letzten Token-Suche */
	protected ?QueryResultInterface $orders = null;

	// Frueher lag hier zusaetzlich phlfrefsRepository (@inject) - die Property wurde
	// nie benutzt und ist deshalb entfallen.
    public function __construct(
        private readonly OrderRepository $orderRepository,
        private readonly PhluserRepository $phluserRepository
    ) {
    }

    /**
     * action list
     */
    public function listAction(): ResponseInterface
    {
    	$this->init();

    	if($this->getPhluserFromFeusers())	//ist ein angemeldeter User in der Phluser
    	{
    		$this->getPageinfo();
    	}

    	// Frueher wurden $order und $data nur INNERHALB des Token-Zweigs gesetzt und
    	// darunter bedingungslos an den View uebergeben -> "undefined variable",
    	// sobald kein Token in der URL stand. Jetzt sauber vorbelegt.
    	$order = null;
    	$data = [];

		$token = $this->getRequestToken();
		if($token !== ''){	//token given, display order/info
			$this->orders=$this->orderRepository->getOrderByToken($token);
			if($this->orders->count()){
				$order=$this->orders->current();

				if($this->phluser){	//logged in user, with rights
					if($this->phluser->getUid()==$order->getPhluserfid()){	//ist aktueller Feuser auch Inhaber der Bestellung
						$data['isowner']=1;
						$data['modus']='admin';
					}
				}else{
					$data['modus']='info';
				}
			}
		}

   		$this->view->assign('order', $order);
        $this->view->assign('data', $data);
        $this->view->assign('settings', $this->settings);

        return $this->htmlResponse();
    }

    /**
     * action status
     */
    public function statusAction(): ResponseInterface
    {
    	$this->init();

    	$order = null;
    	$data = [];

		$token = $this->getRequestToken();
		if($token !== ''){	//token given, display order/info
			$this->orders=$this->orderRepository->getOrderByToken($token);
			if($this->orders->count()){
				$order=$this->orders->current();

				if($this->phluser){	//logged in user, with rights
					if($this->phluser->getUid()==$order->getPhluserfid()){	//ist aktueller Feuser auch Inhaber der Bestellung
						$data['isowner']=1;
						$data['modus']='admin';
					}
				}else{
					//get some company data from Order
					$this->getPhluserById($order->getPhluserfid());
					//redirect here to other status site, if wanted /stated in phlprefs
					$data['modus']='info';
				}
			}
		}

    	$selector['status']=$this->getCategories($this->settings['order']['status'] ?? []);
    	$selector['salutation']=$this->getCategories($this->settings['salutation'] ?? []);
    	$this->view->assign('selector', $selector);
    	$this->view->assign('company', $this->phluser);
   		$this->view->assign('order', $order);
        $this->view->assign('data', $data);
        $this->view->assign('settings', $this->settings);

        return $this->htmlResponse();
    }

    /**
     * action show
     *
     * NICHT REGISTRIERT (Phase 6): weder das Plugin "Order" noch "Orderstatus" gibt
     * diese Action frei - ueber die alte FlexForm-SCA war sie ebenfalls nie
     * erreichbar. Bleibt vorerst stehen, weil Templates/Order/Show.html existiert.
     * Entscheidung ueber Loeschen: Phase 9.
     *
     * @param Order $order
     */
    public function showAction(Order $order): ResponseInterface
    {
        $this->view->assign('order', $order);

        return $this->htmlResponse();
    }

    /**
     * action delete
     *
     * NICHT REGISTRIERT (Phase 6) - und das mit Absicht: die Action loescht eine
     * Bestellung ohne jede Zugriffspruefung. Solange sie in keinem configurePlugin
     * steht, ist sie nicht dispatchbar. Vor einer Reaktivierung braucht sie einen
     * Owner-Check (vgl. $data['isowner'] in listAction/statusAction).
     *
     * @param Order $order
     */
    public function deleteAction(Order $order): ResponseInterface
    {
        $this->addFlashMessage('The object was deleted. Please be aware that this action is publicly accessible unless you implement an access check.', '', ContextualFeedbackSeverity::WARNING);
        $this->orderRepository->remove($order);
        return $this->redirect('list');
    }


	function __________HELPER_________(){}


	/** Order-Token aus der URL (frueher $_REQUEST['t'])
	*@return string leer, wenn keiner uebergeben wurde
	*/
	function getRequestToken(){
		$params = $this->request->getQueryParams();
		return trim((string)($params['t'] ?? ''));
	}


	/** aktuelle Seiten-ID aus dem Request (frueher $GLOBALS['TSFE']->id)
	*@return int
	*/
	function getCurrentPageId(){
		$pageInformation = $this->request->getAttribute('frontend.page.information');
		return $pageInformation ? intval($pageInformation->getId()) : 0;
	}


	/** Datensatz des angemeldeten FE-Users (frueher $GLOBALS['TSFE']->fe_user->user)
	*@return array leer, wenn niemand angemeldet ist
	*/
	function getFrontendUserRecord(){
		$frontendUser = $this->request->getAttribute('frontend.user');
		return is_array($frontendUser->user ?? null) ? $frontendUser->user : [];
	}


	function getPageinfo($data=array()){
		//get cockpit sprecific settings
		$pi=array();
		$feUser=$this->getFrontendUserRecord();
		$pi['pid']=$this->getCurrentPageId();
		$pi['fid']=$feUser['uid'] ?? 0;
		if(isset($this->phluser)){
			$pi['phluid']=$this->phluser->getUid();
			$pi['phlut']=$this->phluser->getToken();
			$pi['csh']=sha1($pi['fid'].$pi['phluid'].$pi['phlut'].($this->settings['ordersalt'] ?? ''));
		}
		foreach ($data as $key=>$item) {
		   $pi[$key] = $item;
		}
		$this->settings['pageInfo']=json_encode($pi);
	}


	/**
	* prepare categories for select box
	*
	* @return array
	*/
	public function getCategories($theArray) {
  	  $categories = array();
  	  $entries = is_array($theArray) ? $theArray : array();
    	foreach ($entries as $key=>$entry) {
    	    $category = new \stdClass();
        	$category->key = $key;
        	$category->value = LocalizationUtility::translate($entry, 'phlorder');
        	$categories[] = $category;
    	}
    	return $categories;
	}


	function ____________INIT_______(){}


	/**initialize FE Views with javascript and CSS from setup
	*
	*@param void
	*@return boolean
	*/
	public function init(){
		// Der frueher hier stehende initSettingsWidthFlexform()-Merge ist entfallen:
		// FlexForm-Felder heissen kuenftig settings.<x> und werden von Extbase nativ
		// nach $this->settings gemergt (Phase 6).
		$this->initStoragePid();
		$pageRenderer=$this->getPageRenderer();
		$this->initJquery($pageRenderer);	//init jquery as in setup
		$this->initCSS($pageRenderer);		//init css files defined in setup
		return true;
	}


	/** frueher mit Versionsweiche auf TYPO3_version - in v13 gibt es nur noch diesen Weg */
	function getPageRenderer(){
		return GeneralUtility::makeInstance(PageRenderer::class);
	}


	/**add the css files from the setup
	*@PARAMETER PageRenderer
	*/
	function initCSS($pageRenderer){
		foreach(($this->settings['cssFile'] ?? []) as $key=>$cssFile){
			$pageRenderer->addCssFile($cssFile);
		}
		return true;
	}


	/**init Javascript and jquery
	*@PARAM PageRenderer
	*@RETURN boolean
	*/
	public function initJquery($pageRenderer){
		// Der frueher hier stehende t3jquery-Zweig (require_once auf class.tx_t3jquery.php,
		// Konstante T3JQUERY) ist entfallen - die Extension gibt es seit Jahren nicht mehr.
		if ($this->settings['initJQuery'] ?? false){	//soll ueberhaupt jQuery geladen werden
			if($this->settings['jQueryToFooter'] ?? false){
				$pageRenderer->addJsFooterLibrary('jquery',$this->settings['pathToJquery']);
			}else{
				$pageRenderer->addJsLibrary('jquery',$this->settings['pathToJquery']);
			}
		}

		$pathToJs = $this->settings['pathToJs'] ?? '';
		if($this->settings['jQueryFilesToFooter'] ?? false){	//jquery at the end of page
			foreach(($this->settings['jscript'] ?? []) as $key=>$file){
				$pageRenderer->addJsFooterFile($pathToJs.$file);
			}
		}else{								//send jquery to the top
			foreach(($this->settings['jscript'] ?? []) as $key=>$file){
			   	$pageRenderer->addJsFile($pathToJs.$file);
			}
		}

		return true;
	}


	/** if setCurrentPageAsStoragePid, use current page as storagePid when nothing is set */
	function initStoragePid(){
		if($this->settings['setCurrentPageAsStoragePid'] ?? false){	//Fallback, wenn nichts gesetzt.
			// frueher: entfernter Alias Tx_Extbase_Configuration_ConfigurationManagerInterface
			$configuration = $this->configurationManager->getConfiguration(ConfigurationManagerInterface::CONFIGURATION_TYPE_FRAMEWORK);

			// Der frueher hier stehende Zweig auf $this->settings['ff']['sourceDB']
			// ist entfallen: das Feld gibt es in der FlexForm nicht.
			if (empty($configuration['persistence']['storagePid'])) {
				$currentPid['persistence']['storagePid'] = $this->getCurrentPageId();
				$this->configurationManager->setConfiguration(array_merge($configuration, $currentPid));
			}
		}
	}


	function ___________DB_________(){}

	function getPhluserFromFeusers(){
		$feUser=$this->getFrontendUserRecord();
		if(!isset($feUser['uid'])){return false;}
		$res=$this->phluserRepository->getPhlUserByFID($feUser['uid']);
		if($res->count()==1){
			$this->phluser=$res->current();
			return true;
		}
		return false;
	}

	function getPhluserById($id){
		if(!$id){return false;}
		$res=$this->phluserRepository->findByUid($id);
		// findByUid() ist auf DomainObjectInterface typisiert; $this->phluser
		// nimmt nur einen Phluser - deshalb hier explizit pruefen.
		if($res instanceof Phluser){
			$this->phluser=$res;
			return true;
		}
		return false;
	}
}
