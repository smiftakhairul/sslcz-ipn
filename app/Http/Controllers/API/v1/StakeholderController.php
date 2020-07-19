<?php

namespace App\Http\Controllers\API\v1;

use App\Http\Controllers\Controller;
use App\Models\Stakeholder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Exception;

class StakeholderController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        try {
            $stakeholders = Stakeholder::all();
            if (!$stakeholders) {
                return $this->filterResponse('No stakeholder found.', 'SUCCESS');
            }
            return $this->filterResponse('Operation Successful.', 'SUCCESS', $stakeholders);
        }
        catch (Exception $exception) {
            $message = $exception->getMessage() ?? 'Operation Unsuccessful.';
            return $this->filterResponse($message);
        }
    }

    public function specific($id)
    {
        try {
            $stakeholder = Stakeholder::find($id);
            if (!$stakeholder) {
                return $this->filterResponse('No stakeholder found.', 'SUCCESS');
            }
            return $this->filterResponse('Operation Successful.', 'SUCCESS', $stakeholder);
        }
        catch (Exception $exception) {
            $message = $exception->getMessage() ?? 'Operation Unsuccessful.';
            return $this->filterResponse($message);
        }
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'stakeholder_uid' => 'required|unique:stakeholders',
            'stakeholder_user' => 'required',
            'stakeholder_pass' => 'required',
            'stakeholder_fcm_authorize_key' => '',
        ]);

        if ($validator->fails()) {
            return $this->filterResponse(['validation' => $validator->errors()]);
        }

        try {
            $data = $this->filterRequest($request);
            $stakeholder = Stakeholder::create($data);

            if ($stakeholder) {
                return $this->filterResponse('Operation Successful.', 'SUCCESS', $stakeholder);
            }
        }
        catch (Exception $exception) {
            $message = $exception->getMessage() ?? 'Operation Unsuccessful.';
            return $this->filterResponse($message);
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'stakeholder_uid' => 'unique:stakeholders,stakeholder_uid,'.$id,
            'stakeholder_user' => '',
            'stakeholder_pass' => '',
            'stakeholder_fcm_authorize_key' => '',
        ]);

        if ($validator->fails()) {
            return $this->filterResponse(['validation' => $validator->errors()]);
        }

        try {
            $stakeholder = Stakeholder::find($id);
            if (!$stakeholder) {
                return $this->filterResponse('No stakeholder found.', 'SUCCESS');
            }

            $data = $this->filterRequest($request, $stakeholder);
            if ($stakeholder->update($data)) {
                return $this->filterResponse('Operation Successful.', 'SUCCESS', $stakeholder);
            }
        }
        catch (Exception $exception) {
            $message = $exception->getMessage() ?? 'Operation Unsuccessful.';
            return $this->filterResponse($message);
        }
    }

    /**
     * @param Request $request
     * @param $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Request $request, $id)
    {
        try {
            $stakeholder = Stakeholder::find($id);
            if (!$stakeholder) {
                return $this->filterResponse('No stakeholder found.', 'SUCCESS');
            }
            if ($stakeholder->delete()) {
                return $this->filterResponse('Operation Successful.', 'SUCCESS');
            }
        }
        catch (Exception $exception) {
            $message = $exception->getMessage() ?? 'Operation Unsuccessful.';
            return $this->filterResponse($message);
        }
    }


//    filters
    private function filterRequest(Request $request, $item = null)
    {
        $data['stakeholder_uid'] = $request->input('stakeholder_uid') ?? $item->stakeholder_uid ?? '';
        $data['user'] = $request->input('stakeholder_user') ?? $item->user ?? '';
        $data['pass'] = $request->input('stakeholder_pass')
            ? encryptString($request->input('stakeholder_pass'))
            : $item->pass ?? '';
        if ($request->has('stakeholder_fcm_authorize_key')) {
            $data['fcm_authorize_key'] = $request->input('stakeholder_fcm_authorize_key') ?? $item->fcm_authorize_key ?? '';
        }
        $data['url'] = config('misc.sms.url');

        return $data;
    }

    private function filterResponse($message, $status = 'ERROR', $data = [])
    {
        return response()->json([
            'status' => $status,
            'data' => $data,
            'message' => $message
        ]);
    }
}
