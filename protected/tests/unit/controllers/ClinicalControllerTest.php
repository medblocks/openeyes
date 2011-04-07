<?php
class ClinicalControllerTest extends CDbTestCase
{
	public $fixtures = array(
		'users' => 'User',
		'patients' => 'Patient',
		'episodes' => 'Episode',
		'eventTypes' => 'EventType',
		'events' => 'Event',
		'serviceSpecialtyAssignments' => 'ServiceSpecialtyAssignment',
		'firms' => 'Firm',
		'services' => 'Service',
		'specialties' => 'Specialty',
		'siteElementTypes' => 'SiteElementType',
		'elementHistories' => 'ElementHistory',
		'elementPOHs' => 'ElementPOH',
	);

	protected $controller;

	protected function setUp()
	{
		$this->controller = new ClinicalController('ClinicalController');
		parent::setUp();
	}

	public function dataProvider_EventTypesForAccidentAndEmergencySpecialty()
	{
		return array(
			array('1','9'),
		);
	}

	public function testActionIndex_RendersIndexView()
	{
		$mockController = $this->getMock('ClinicalController', array('render'),
			array('ClinicalController'));
		$mockController->expects($this->any())
			->method('render')
			->with('index');
		$mockController->actionIndex();
	}

	public function testActionView_InvalidEvent_ThrowsException()
	{
		$fakeId = 5829;

		$this->setExpectedException('CHttpException', 'Invalid event id.');
		$this->controller->actionView($fakeId);
	}

	public function testActionView_ValidElement_RendersViewView()
	{
		$event = $this->events('event1');

		$elementHistory = $this->elementHistories('elementHistory1');
		$elementPOH = $this->elementPOHs('elementPOH1');

		$expectedElements = array($elementHistory, $elementPOH);

		$mockController = $this->getMock('ClinicalController', array('render', 'getUserId'), array('ClinicalController'));

		$mockService = $this->getMock('ClinicalService',
			array('getElements'));

		$mockService->expects($this->once())
			->method('getElements')
			->with(null, null, null, 1, $event)
			->will($this->returnValue($expectedElements));

		$mockController->service = $mockService;

		$mockController->expects($this->any())
			->method('render')
			->with('view', array('elements' => $expectedElements));

		$mockController->expects($this->once())
			->method('getUserId')
			->will($this->returnValue(1));

		$mockController->actionView($event->id);
	}

/*
// Currently there is no way of testing beforeAction.
	public function testBeforeAction()
	{
		$mockController = $this->getMock('ClinicalController',
			array('checkPatientId', 'listEpisodesAndEventTypes'),
			array('ClinicalController'), 'Mock_ClinicalController', false);

		$mockController->selectedFirmId = $this->firms['firm1']['id'];
		$mockController->expects($this->once())
			->method('checkPatientId');
		$mockController->expects($this->once())
			->method('listEpisodesAndEventTypes');

		$mockController->beforeAction('index');
	}
*/
	public function testActionCreate_MissingEventTypeId_ThrowsException()
	{
		$this->setExpectedException('CHttpException', 'No event_type_id specified.');
		$this->controller->actionCreate();
	}

	public function testActionCreate_InvalidEventTypeId_ThrowsException()
	{
		$_GET['event_type_id'] = 927490278592;

		$this->setExpectedException('CHttpException', 'Invalid event_type_id.');
		$this->controller->actionCreate();
	}

	public function testActionView_ValidElement_RendersCreateView()
	{
		$patientId = 1;
		$eventTypeId = 1;
		$_GET['event_type_id'] = $eventTypeId;

		$event = $this->events('event1');
		$eventType = $this->eventTypes('eventType1');
		$firm = $this->firms('firm1');

		$elementHistory = $this->elementHistories('elementHistory1');
		$elementPOH = $this->elementPOHs('elementPOH1');

		$expectedElements = array($elementHistory, $elementPOH);

		$mockController = $this->getMock('ClinicalController', array('render', 'getUserId'), array('ClinicalController'));
		$mockController->patientId = $patientId;
		$mockController->firm = $firm;

		$mockService = $this->getMock('ClinicalService',
			array('getElements'));

		$mockService->expects($this->once())
			->method('getElements')
			->with($eventType, $firm, $patientId, 1)
			->will($this->returnValue($expectedElements));

		$mockController->service = $mockService;

		$mockController->expects($this->any())
			->method('render')
			->with('create', array(
				'elements' => $expectedElements,
				'eventTypeId' => $eventTypeId
			));

		$mockController->expects($this->once())
			->method('getUserId')
			->will($this->returnValue(1));

		$mockController->actionCreate($event->id);
	}

	public function testActionCreate_ValidPostData_RendersViewView()
	{
		$_POST['elementPOH'] = $this->elementPOHs['elementPOH1'];
		$_POST['elementHistory'] = $this->elementHistories['elementHistory1'];
		$_POST['action'] = 'create';
		$_GET['event_type_id'] = 1;

		$event = $this->events('event1');
		$firm = $this->firms('firm1');
		$eventType = $this->eventTypes('eventType1');
		$patientId = 1;

		$elementHistory = $this->elementHistories('elementHistory1');
		$elementPOH = $this->elementPOHs('elementPOH1');

		$expectedElements = array($elementHistory, $elementPOH);

		$mockController = $this->getMock('ClinicalController',
			array('render', 'redirect', 'getUserId'), array('ClinicalController'));

		$mockController->expects($this->once())
			->method('redirect')
			->with(array('view', 'id' => $event->id));

		$mockController->expects($this->any())
			->method('getUserId')
			->will($this->returnValue(1));

		$mockService = $this->getMock('ClinicalService',
			array('getElements', 'createElements'));

		$mockService->expects($this->once())
			->method('getElements')
			->with($eventType, $firm, $patientId, 1)
			->will($this->returnValue($expectedElements));

		$mockService->expects($this->once())
			->method('createElements')
			->with($expectedElements, $_POST, $firm, $patientId, 1, $eventType->id)
			->will($this->returnValue(1));

		$mockController->firm = $firm;
		$mockController->service = $mockService;
		$mockController->patientId = $patientId;
		$mockController->actionCreate($event->id);
	}

	public function testActionUpdate_InvalidFirmSelected_ThrowsException()
	{
		$event = $this->events('event1');
		$this->controller->firm = $this->firms('firm2');

		$this->setExpectedException('CHttpException', 'The firm you are using is not associated with the specialty for this event.');
		$this->controller->actionUpdate($event->id);
	}

	public function testActionUpdate_InvalidData_RendersUpdateView()
	{
		$event = $this->events('event1');
		$firm = $this->firms('firm1');
		$userId = 1;

		$this->populateObjects($event, $firm);

		$elementHistory = $this->elementHistories('elementHistory1');
		$elementPOH = $this->elementPOHs('elementPOH1');

		$expectedElements = array($elementHistory, $elementPOH);

		$mockController = $this->getMock('ClinicalController', array('render', 'getUserId'), array('ClinicalController'));

		$mockService = $this->getMock('ClinicalService',
			array('getElements'));

		$mockService->expects($this->once())
			->method('getElements')
			->with(null, null, null, $userId, $event)
			->will($this->returnValue($expectedElements));

		$mockController->service = $mockService;
		$mockController->firm = $firm;

		$mockController->expects($this->any())
			->method('render')
			->with('update', array('id' => $event->id, 'elements' => $expectedElements));

		$mockController->expects($this->once())
			->method('getUserId')
			->will($this->returnValue($userId));

		$mockController->actionUpdate($event->id);
	}

	public function testActionUpdate_ValidPostData_RendersViewView()
	{
		$userId = 1;
		$_POST = $this->events['event1'];
		$_POST['action'] = 'update';

		$event = $this->events('event1');
		$firm = $this->firms('firm1');

		$this->populateObjects($event, $firm);

		$elementHistory = $this->elementHistories('elementHistory1');
		$elementPOH = $this->elementPOHs('elementPOH1');

		$expectedElements = array($elementHistory, $elementPOH);

		$mockController = $this->getMock('ClinicalController',
			array('render', 'redirect', 'getUserId'), array('ClinicalController'));
		$mockController->expects($this->once())
			->method('redirect')
			->with(array('view', 'id' => $event->id));

		$mockController->expects($this->once())
			->method('getUserId')
			->will($this->returnValue($userId));

		$mockService = $this->getMock('ClinicalService',
			array('getElements', 'updateElements'));

		$mockService->expects($this->once())
			->method('getElements')
			->with(null, null, null, $userId, $event)
			->will($this->returnValue($expectedElements));

		$mockService->expects($this->once())
			->method('updateElements')
			->with($expectedElements, $_POST, $event)
			->will($this->returnValue(true));

		$mockController->firm = $firm;
		$mockController->service = $mockService;
		$mockController->actionUpdate($event->id);
	}

	public function testListEpisodes()
	{
		$patient = $this->patients('patient1');
		$firm = $this->firms('firm1');

		$mockController = $this->getMock('ClinicalController', array('checkPatientId'), array('ClinicalController'));
		$mockController->expects($this->any())->method('checkPatientId');
		$mockController->patientId = $patient->id;
		$mockController->selectedFirmId = $firm->id;
		$mockController->firm = $firm;

		$this->assertNull($mockController->episodes);
		$mockController->listEpisodesAndEventTypes();
		$this->assertEquals($patient->episodes, $mockController->episodes);
	}

	/**
	 * @dataProvider dataProvider_EventTypesForAccidentAndEmergencySpecialty
	 */
/*
// This test should be in BaseControllerTest.php. Also, it doesn't work.
	public function testListEventTypes($eventTypesArray)
	{
		// test that $mockController->eventTypes equals the eventtypes for the given firm's specialty
		// we should have 1 and 9 for firm/specialty 1
		$patient = $this->patients('patient1');
		$firm = $this->firms('firm1');
		$app->session['selected_firm_id'] = $firm->id;
		$mockController = $this->getMock('ClinicalController', array('checkPatientId'), array('ClinicalController'));
		$mockController->expects($this->any())->method('checkPatientId');
		$mockController->patientId = $patient->id;

		$this->assertNull($mockController->eventTypes);
		$mockController->selectedFirmId = $firm->id;
		$mockController->firm = $firm;
		$mockController->listEpisodesAndEventTypes();

		$count = 0;
		foreach ($mockController->eventTypes as $eventType) {
			$this->assertEquals($eventTypesArray[$count], $eventType->id);
			$count++;
		}
	}
*/

	/**
	 * These two stupid bits of code are here to ensure that the event and firm objects
	 * match properly, else the test fails on the
	 *		//	'The firm you are using is not associated with the specialty for this event.'
	 *		// test.
	 *
	 * @param object $event
	 * @param object $firm
	 */
	public function populateObjects($event, $firm)
	{
		$foo = $event->episode->firm->serviceSpecialtyAssignment->specialty_id;
		$bar = $firm->serviceSpecialtyAssignment->specialty_id;
	}
}
