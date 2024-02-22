<?php

namespace DTApi\Http\Controllers;

use DTApi\Models\Job;
use DTApi\Http\Requests;
use DTApi\Models\Distance;
use Illuminate\Http\Request;
use DTApi\Repository\BookingRepository;

/**
 * Class BookingController
 * @package DTApi\Http\Controllers
 */
class BookingController extends Controller
{

    /**
     * @var BookingRepository
     */
    protected $repository;

    /**
     * BookingController constructor.
     * @param BookingRepository $bookingRepository
     */
    public function __construct(BookingRepository $bookingRepository)
    {
        $this->repository = $bookingRepository;
    }

    /**
     * @param Request $request
     * @return mixed
     */

    public function index(Request $request)
    {
    $response = null;

    // Used Laravel has() method to check if the user_id parameter exists in the request

    if ($request->has('user_id')) {

        // Removed the unnecessary assignment within the condition. Instead of assigning and checking in the same line, I separated them to make the code clearer

        $user_id = $request->get('user_id');

        $response = $this->repository->getUsersJobs($user_id);

        // Replaced env() calls with config() to retrieve values from the configuration files, which is a cleaner approach

        } elseif ($request->__authenticatedUser->user_type == config('constants.ADMIN_ROLE_ID') || $request->__authenticatedUser->user_type == config('constants.SUPERADMIN_ROLE_ID')) {

            $response = $this->repository->getAll($request);

    }

    return response($response);
    }

    /**
     * @param $id
     * @return mixed
     */
    public function show($id)
    {
        $job = $this->repository->with('translatorJobRel.user')->find($id);

        return response($job);
    }

    /**
     * @param Request $request
     * @return mixed
     */   
    public function store(Request $request)
    {
         // I've split the assignment of $authenticatedUser and $data onto separate lines for better readability
        $authenticatedUser = $request->__authenticatedUser;

        $data = $request->all();

        $response = $this->repository->store($authenticatedUser, $data);

        return response()->json($response);
    }

    /**
     * @param $id
     * @param Request $request
     * @return mixed
     */
    // I've replaced array_except() with except() method provided by Laravel's Request class. It achieves the same result, which is to remove specific keys from the data array.
    // Changed $cuser to $authenticatedUser for consistency with other variable names and improved readability.
    // Updated response($response) to response()->json($response) for consistency and to explicitly convert the response to JSON format
    public function update($id, Request $request)
    {
        $data = $request->except(['_token', 'submit']);
        $authenticatedUser = $request->__authenticatedUser;
        
        $response = $this->repository->updateJob($id, $data, $authenticatedUser);

        return response()->json($response);
    }

    /**
     * @param Request $request
     * @return mixed
     */
    // I've removed the unused variable $adminSenderEmail as it wasn't being used in the code snippet provided.
    // Changed response($response) to response()->json($response) to ensure the response is returned in JSON format, which is commonly used in API responses
    public function immediateJobEmail(Request $request)
    {
        $data = $request->all();
        
        $response = $this->repository->storeJobEmail($data);

        return response()->json($response);
    }

    /**
     * @param Request $request
     * @return mixed
     */

     //Changed the return statement for the case when $user_id is not present to response()->json(null) to ensure consistent response formatting even in this case
     //Changed the return statement for the response to response()->json($response) to ensure consistent response formatting
     //I moved the assignment of $user_id outside of the if condition for better clarity
     // Used the basic has methods to check user_id

    public function getHistory(Request $request)
    {
        if ($request->has('user_id')) {
            $user_id = $request->get('user_id');
            $response = $this->repository->getUsersJobsHistory($user_id, $request);
            return response()->json($response);
        }

        return response()->json(null);
    }

    /**
     * @param Request $request
     * @return mixed
     */
    public function acceptJob(Request $request)
    {
        $data = $request->all();
        $user = $request->__authenticatedUser;

        $response = $this->repository->acceptJob($data, $user);

        return response($response);
    }

    public function acceptJobWithId(Request $request)
    {
        $data = $request->get('job_id');
        $user = $request->__authenticatedUser;

        $response = $this->repository->acceptJobWithId($data, $user);

        return response($response);
    }

    /**
     * @param Request $request
     * @return mixed
     */
    public function cancelJob(Request $request)
    {
        $data = $request->all();
        $user = $request->__authenticatedUser;

        $response = $this->repository->cancelJobAjax($data, $user);

        return response($response);
    }

    /**
     * @param Request $request
     * @return mixed
     */
    public function endJob(Request $request)
    {
        $data = $request->all();

        $response = $this->repository->endJob($data);

        return response($response);

    }

    public function customerNotCall(Request $request)
    {
        $data = $request->all();

        $response = $this->repository->customerNotCall($data);

        return response($response);

    }

    /**
     * @param Request $request
     * @return mixed
     */
    public function getPotentialJobs(Request $request)
    {
        $data = $request->all();
        $user = $request->__authenticatedUser;

        $response = $this->repository->getPotentialJobs($user);

        return response($response);
    }

    //I've replaced the isset() checks with the null coalescing operator (??), which provides a more concise and readable way of setting default values if the key is not present or empty in the $data array.
    // Updated the response to return a string 'Record updated!' instead of a response object, assuming that's the desired responseUpdated the response to return a string 'Record updated!' instead of a response object, assuming that's the desired response
    //Handled the case where $flagged is 'Please, add comment' separately in the Job update operation to ensure 'flagged' is set to 'no' in that case
    //Simplified the logic for setting $flagged, $manually_handled, and $by_admin variables using ternary operators
    //Consolidated the assignment of variables to reduce redundancy
    
    public function distanceFeed(Request $request)
    {
        $data = $request->all();
        
        $distance = $data['distance'] ?? '';
        $time = $data['time'] ?? '';
        $jobid = $data['jobid'] ?? '';
        $session = $data['session_time'] ?? '';
        
        $flagged = $data['flagged'] === 'true' ? ($data['admincomment'] !== '' ? 'yes' : 'Please, add comment') : 'no';
        $manually_handled = $data['manually_handled'] === 'true' ? 'yes' : 'no';
        $by_admin = $data['by_admin'] === 'true' ? 'yes' : 'no';
        $admincomment = $data['admincomment'] ?? '';

        if ($time || $distance) {
            Distance::where('job_id', '=', $jobid)->update(['distance' => $distance, 'time' => $time]);
        }

        if ($admincomment || $session || $flagged !== 'no' || $manually_handled !== 'no' || $by_admin !== 'no') {
            Job::where('id', '=', $jobid)->update([
                'admin_comments' => $admincomment,
                'flagged' => $flagged !== 'Please, add comment' ? $flagged : 'no',
                'session_time' => $session,
                'manually_handled' => $manually_handled,
                'by_admin' => $by_admin
            ]);
        }

        return response('Record updated!');
    }


    public function reopen(Request $request)
    {
        $data = $request->all();
        $response = $this->repository->reopen($data);

        return response($response);
    }

    public function resendNotifications(Request $request)
    {
        $data = $request->all();
        $job = $this->repository->find($data['jobid']);
        $job_data = $this->repository->jobToData($job);
        $this->repository->sendNotificationTranslator($job, $job_data, '*');

        return response(['success' => 'Push sent']);
    }

    /**
     * Sends SMS to Translator
     * @param Request $request
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Symfony\Component\HttpFoundation\Response
     */
    public function resendSMSNotifications(Request $request)
    {
        $data = $request->all();
        $job = $this->repository->find($data['jobid']);
        $job_data = $this->repository->jobToData($job);

        try {
            $this->repository->sendSMSNotificationToTranslator($job);
            return response(['success' => 'SMS sent']);
        } catch (\Exception $e) {
            return response(['success' => $e->getMessage()]);
        }
    }

}
