<?php

namespace App\Http\Controllers;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

class ZoomController extends Controller
{
	/**
	 * Return auth token
	 *
	 * @return string
	 */
	public static function getToken()
	{
		$filename = public_path("zoom.txt");
		if (File::exists($filename)) {
			$file = fopen($filename, 'r');
			$expires = fgets($file);
			if (time() < strtotime($expires) - 30) {
				$token = fgets($file);
				if ($token != '') {
					fclose($file);
					return $token;
				}
			}
			fclose($file);
		}

		$curl = curl_init();
		$data = [
			"grant_type" => "account_credentials",
			"account_id" => env('ZOOM_ACCOUNT_ID'),
		];
		curl_setopt_array($curl, array(
			CURLOPT_URL => "https://zoom.us/oauth/token",
			CURLOPT_POSTFIELDS => http_build_query($data),
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_CUSTOMREQUEST => "POST",
			CURLOPT_HTTPHEADER => array(
				"Authorization: Basic " . base64_encode(env('ZOOM_CLIENT_ID') . ':' . env('ZOOM_CLIENT_SECRET')),
				"content-type: application/x-www-form-urlencoded",
				"withCredentials: true",
				"X-Requested-With: XMLHttpRequest",
			),
		));
		$responseStr = curl_exec($curl);
		$response = json_decode($responseStr);
		$err = curl_error($curl);
		curl_close($curl);

		if ($err) {
			Log::info("cURL Error #:" . $err);
			abort(500);
		} else {
			if (!isset($response->access_token)) {
				Log::info($responseStr);
				abort(500);
			}
			$file = fopen($filename, 'w');

			fputs($file, (time() + 3600) . "\n");
			fputs($file, $response->access_token);
			fclose($file);
			return $response->access_token;
		}
	}

	/**
	 * Create zoom meeting
	 * 
	 * @return int meeting id
	 */
	public static function createMeeting(string $title, $start_time, int $duration)
	{
		$curl = curl_init();
		$data = [
			"topic" => $title,
			"type" => 2,
			"start_time" => $start_time,
			"duration" => 	$duration,
			"password" => "12345678",
		];
		curl_setopt_array($curl, array(
			CURLOPT_URL => "https://api.zoom.us/v2/users/" . env('ZOOM_USER_ID') . "/meetings",
			CURLOPT_POSTFIELDS => json_encode($data),
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_CUSTOMREQUEST => "POST",
			CURLOPT_HTTPHEADER => array(
				"Authorization: Bearer " . self::getToken(),
				"content-type: application/json",
				"withCredentials: true",
				"X-Requested-With: XMLHttpRequest",
			),
		));

		$responseStr = curl_exec($curl);
		$response = json_decode($responseStr);
		$err = curl_error($curl);
		curl_close($curl);
		if ($err) {
			Log::info("cURL Error #:" . $err);
			abort(500);
		} else {
			if (!isset($response->id)) {
				Log::info($responseStr);
				abort(500);
			}
			return $response->id;
		}
	}

	/**
	 * Return meeting info
	 *
	 * @return stdClass
	 */
	public static function getMeetingInfo(int $meetingId)
	{
		if($meetingId == null){
			return null;
		}
		$curl = curl_init();
		curl_setopt_array($curl, array(
			CURLOPT_URL => "https://api.zoom.us/v2/meetings/" . $meetingId,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_CUSTOMREQUEST => "GET",
			CURLOPT_HTTPHEADER => array(
				"Authorization: Bearer " . self::getToken(),
				"content-type: application/json",
				"withCredentials: true",
				"X-Requested-With: XMLHttpRequest",
			),
		));

		$response = curl_exec($curl);
		$err = curl_error($curl);
		curl_close($curl);

		if ($err) {
			Log::info("cURL Error #:" . $err);
			abort(500);
		} else {
			$response = json_decode($response, true);
			if(array_key_exists('id', $response)){
				return $response;
			}else{
				Log::info($response);
				abort(500);
			}
				
		}
	}

	/**
	 * Delete zoom meeting
	 *
	 * @return int response code
	 */
	public static function deleteMeeting(int $meetingId)
	{
		$curl = curl_init();
		curl_setopt_array($curl, array(
			CURLOPT_URL => "https://api.zoom.us/v2/meetings/" . $meetingId,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_CUSTOMREQUEST => "DELETE",
			CURLOPT_HTTPHEADER => array(
				"Authorization: Bearer " . self::getToken(),
				"content-type: application/x-www-form-urlencoded",
				"withCredentials: true",
				"X-Requested-With: XMLHttpRequest",
			),
		));

		$response = curl_exec($curl);
		if ($response != '') {
			Log::info($response);
		}
		$responseCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
		$err = curl_error($curl);
		curl_close($curl);

		if ($err) {
			Log::info("(deleting meeting) cURL Error #:" . $err);
		}
		return $responseCode;
	}

	/**
	 * Return info about all meetings
	 *
	 * @return Object
	 */
	public function index($page = 1)
	{
		$meetings = Cache::rememberForever('meetings', function () {
			$data = [
				"page_size" => "300",
			];
			$curl = curl_init();
			curl_setopt_array($curl, array(
				CURLOPT_URL => "https://api.zoom.us/v2/users/" . env('ZOOM_USER_ID') . "/meetings",
				CURLOPT_RETURNTRANSFER => true,
				CURLOPT_POSTFIELDS => http_build_query($data),
				CURLOPT_CUSTOMREQUEST => "GET",
				CURLOPT_HTTPHEADER => array(
					"Authorization: Bearer " . self::getToken(),
					"content-type: application/x-www-form-urlencoded",
					"withCredentials: true",
					"X-Requested-With: XMLHttpRequest",
				),
			));

			$response = json_decode(curl_exec($curl));
			$err = curl_error($curl);

			if ($err) {
				Log::info("cURL Error #:" . $err);
				curl_close($curl);
				abort(500);
			}
			$meetings = collect($response->meetings);

			if ($response->total_records / $response->page_size > 1) {
				$pageCount = ceil($response->total_records / $response->page_size);
				for ($i = 2; $i <= $pageCount; $i++) {
					$data = [
						"page_size" => "300",
						"page_number" => $i,
					];
					curl_setopt_array($curl, array(
						CURLOPT_URL => "https://api.zoom.us/v2/users/" . env('ZOOM_USER_ID') . "/meetings",
						CURLOPT_RETURNTRANSFER => true,
						CURLOPT_POSTFIELDS => http_build_query($data),
						CURLOPT_CUSTOMREQUEST => "GET",
						CURLOPT_HTTPHEADER => array(
							"Authorization: Bearer " . self::getToken(),
							"content-type: application/x-www-form-urlencoded",
							"withCredentials: true",
							"X-Requested-With: XMLHttpRequest",
						),
					));

					$response = json_decode(curl_exec($curl));
					$err = curl_error($curl);

					if ($err) {
						Log::info("cURL Error #:" . $err);
						curl_close($curl);
						abort(400);
					}

					$meetings = $meetings->merge($response->meetings);
				}
			}

			curl_close($curl);
			return $meetings;
		});
		$meetings = new LengthAwarePaginator($meetings->forPage($page, 15), $meetings->count(), 15, $page);

		$meetingsArray = [];
		foreach ($meetings as $key => $meeting) {
			$meetingsArray[$key] = [];
			$meetingsArray[$key]['uuid'] = $meeting->uuid;
			$meetingsArray[$key]['id'] = $meeting->id;
			$meetingsArray[$key]['host_id'] = $meeting->host_id;
			$meetingsArray[$key]['topic'] = $meeting->topic;
			$meetingsArray[$key]['type'] = $meeting->type;
			$meetingsArray[$key]['start_time'] = $meeting->start_time;
			$meetingsArray[$key]['timezone'] = $meeting->timezone;
			$meetingsArray[$key]['created_at'] = $meeting->created_at;
			$meetingsArray[$key]['join_url'] = $meeting->join_url;
		}

		$pageInfo = [];
		$pageInfo['meetings'] = $meetingsArray;
		$pageInfo['maxPage'] = $meetings->lastPage();

		return json_encode($pageInfo);
	}
}
