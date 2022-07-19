<?php

/**
 * @group sample-data
 * @group reference-data
 */
class OphDrPrescription_DefaultControllerTest extends OEDbTestCase
{
    use CreatesControllers;
    use MocksSession;
    use InteractsWithMedication;
    use WithTransactions;

    public string $moduleCls = 'OphDrPrescription';

    public function setUp()
    {
        parent::setUp();
        // not sure why this isn't automagically happening in config
        \Yii::import('application.modules.OphDrPrescription.*');
    }

    public function getDefaultController($methods = [])
    {
        return $this->getController(DefaultController::class, $methods);
    }

    /** @test */
    public function dmd_drug_can_be_retrieved()
    {
        $medication = $this->createDMDMedication();
        
        $requestResult = $this->performDrugListRequest(['term' => $medication->preferred_term]);
        
        $returnedIds = array_map(
            function ($data) {
                return $data['id'];
            },
            $requestResult
        );

        $this->assertContains($medication->id, $returnedIds);
    }

    /**
     * Carry out the actionDrugList request with the given request ($_GET) parameters
     */
    protected function performDrugListRequest(array $params = [])
    {
        $this->mockAjaxRequest();
        $this->mockCurrentContext();
        $_GET = $params;

        $outputCapture = [];
        $controller = $this->getControllerForJsonRenderCapture('DefaultController', ['checkFormAccess'], $outputCapture);

        $controller->method('checkFormAccess')
            ->willReturn(true);
        
        $action = $controller->createAction('drugList');
        
        $controller->runAction($action);

        return $outputCapture;
    }

    /**
     * Generates a controller of the given class, and puts the json rendered content into 
     * the provided $outputCapture reference.
     */
    protected function getControllerForJsonRenderCapture($cls, $methods = [], &$outputCapture)
    {
        $methods = array_merge($methods, ['renderJson']);
        $controller =  $this->getController($cls, $methods);

        $controller->method('renderJSON')
            ->will($this->returnCallback(function ($data) use (&$outputCapture) {
                $outputCapture = $data;
            }));

        return $controller;
    }

    /**
     * Mock the current Yii request object to indicate an ajax request
     */
    protected function mockAjaxRequest()
    {
        $request = $this->getMockRequest();
        $request->method('getIsAjaxRequest')
            ->will($this->returnValue(true));
        \Yii::app()->setComponent('request', $request);
    }

    protected function getMockRequest()
    {
        $request = $this->getMockBuilder(\CHttpRequest::class)
            ->disableOriginalConstructor()
            ->getMock();

        $request->method('getPost')
            ->will($this->returnCallback(function($key, $default) {
                return $default; // always return the default
            }));

        return $request;
    }
}