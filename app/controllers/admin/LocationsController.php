<?php namespace Controllers\Admin;

use AdminController;
use Input;
use Lang;
use Location;
use Redirect;
use Setting;
use DB;
use Sentry;
use Str;
use Validator;
use View;

use Symfony\Component\HttpFoundation\JsonResponse;

class LocationsController extends AdminController
{
    /**
     * Show a list of all the locations.
     *
     * @return View
     */

    public function getIndex()
    {
        // Grab all the locations
        $locations = Location::orderBy('created_at', 'DESC')->with('parent','assets','assignedassets')->get();

        // Show the page
        return View::make('backend/locations/index', compact('locations'));
    }


    /**
     * Location create.
     *
     * @return View
     */
    public function getCreate()
    {
        $locations = Location::orderBy('name','ASC')->get();

        $location_options_array = Location::getLocationHierarchy($locations);
        $location_options = Location::flattenLocationsArray($location_options_array);
        $location_options = array('' => 'Top Level') + $location_options;

        return View::make('backend/locations/edit')
        ->with('location_options',$location_options)
        ->with('location',new Location);
    }


    /**
     * Location create form processing.
     *
     * @return Redirect
     */
    public function postCreate()
    {

        // get the POST data
        $new = Input::all();

        // create a new location instance
        $location = new Location();

        // attempt validation
        if ($location->validate($new)) {

            // Save the location data
            $location->name            	= e(Input::get('name'));
            if (Input::get('parent_id')=='') {
                $location->parent_id		= null;
            } else {
                $location->parent_id		= e(Input::get('parent_id'));
            }
            $location->currency			= Input::get('currency','$');
            $location->address			= e(Input::get('address'));
            $location->address2			= e(Input::get('address2'));
            $location->city    			= e(Input::get('city'));
            $location->state    		= e(Input::get('state'));
            $location->country    		= e(Input::get('country'));
            $location->zip    			= e(Input::get('zip'));
            $location->user_id          = Sentry::getId();

            // Was the asset created?
            if($location->save()) {
                // Redirect to the new location  page
                return Redirect::to("admin/settings/locations")->with('success', Lang::get('admin/locations/message.create.success'));
            }
        } else {
            // failure
            $errors = $location->errors();
            return Redirect::back()->withInput()->withErrors($errors);
        }

        // Redirect to the location create page
        return Redirect::to('admin/settings/locations/create')->with('error', Lang::get('admin/locations/message.create.error'));

    }

    public function store()
    {
      $new = Input::all();

      $new['currency']=Setting::first()->default_currency;

      // create a new location instance
      $location = new Location();

      // attempt validation
      if ($location->validate($new)) {

          // Save the location data
          $location->name            	= e(Input::get('name'));
          // if (Input::get('parent_id')=='') {
          //     $location->parent_id		= null;
          // } else {
          //     $location->parent_id		= e(Input::get('parent_id'));
          // }
          $location->currency			=  Setting::first()->default_currency; //e(Input::get('currency'));
          $location->address			= ''; //e(Input::get('address'));
          // $location->address2			= e(Input::get('address2'));
          $location->city    			= e(Input::get('city'));
          $location->state    		= '';//e(Input::get('state'));
          $location->country    		= e(Input::get('country'));
          // $location->zip    			= e(Input::get('zip'));
          $location->user_id          = Sentry::getId();

          // Was the asset created?
          if($location->save()) {
              // Redirect to the new location  page
              return JsonResponse::create($location);
              //return Redirect::to("admin/settings/locations")->with('success', Lang::get('admin/locations/message.create.success'));
          } else {
            return JsonResponse::create(["error" => "Couldn't save Location"],500);
          }
      } else {
          // failure
          $errors = $location->errors();
          return JsonResponse::create(["error" => "Failed validation: ".print_r($errors->all('<li>:message</li>'),true)],500);
      }

      // Redirect to the location create page
      return Redirect::to('admin/settings/locations/create')->with('error', Lang::get('admin/locations/message.create.error'));
    }


    /**
     * Location update.
     *
     * @param  int  $locationId
     * @return View
     */
    public function getEdit($locationId = null)
    {
        // Check if the location exists
        if (is_null($location = Location::find($locationId))) {
            return Redirect::to('admin/settings/locations')->with('error', Lang::get('admin/locations/message.does_not_exist'));
        }

        // Show the page
        $locations = Location::orderBy('name','ASC')->get();
        $location_options_array = Location::getLocationHierarchy($locations);
        $location_options = Location::flattenLocationsArray($location_options_array);
        $location_options = array('' => 'Top Level') + $location_options;

        return View::make('backend/locations/edit', compact('location'))->with('location_options',$location_options);
    }


    /**
     * Location update form processing page.
     *
     * @param  int  $locationId
     * @return Redirect
     */
    public function postEdit($locationId = null)
    {
        // Check if the location exists
        if (is_null($location = Location::find($locationId))) {
            // Redirect to the blogs management page
            return Redirect::to('admin/settings/locations')->with('error', Lang::get('admin/locations/message.does_not_exist'));
        }

        //attempt to validate
        $validator = Validator::make(Input::all(), $location->validationRules($locationId));

        if ($validator->fails())
        {
            // The given data did not pass validation
            return Redirect::back()->withInput()->withErrors($validator->messages());
        }
        // attempt validation
        else {

            // Update the location data
            $location->name         = e(Input::get('name'));
            if (Input::get('parent_id')=='') {
                $location->parent_id		= null;
            } else {
                $location->parent_id		= e(Input::get('parent_id',''));
            }
            $location->currency			= Input::get('currency','$');
            $location->address			= e(Input::get('address'));
            $location->address2			= e(Input::get('address2'));
            $location->city    			= e(Input::get('city'));
            $location->state    		= e(Input::get('state'));
            $location->country    	= e(Input::get('country'));
            $location->zip    		  = e(Input::get('zip'));

            // Was the asset created?
            if($location->save()) {
                // Redirect to the saved location page
                return Redirect::to("admin/settings/locations/")->with('success', Lang::get('admin/locations/message.update.success'));
            }
        }

        // Redirect to the location management page
        return Redirect::to("admin/settings/locations/$locationId/edit")->with('error', Lang::get('admin/locations/message.update.error'));

    }

    /**
     * Delete the given location.
     *
     * @param  int  $locationId
     * @return Redirect
     */
    public function getDelete($locationId)
    {
        // Check if the location exists
        if (is_null($location = Location::find($locationId))) {
            // Redirect to the blogs management page
            return Redirect::to('admin/settings/locations')->with('error', Lang::get('admin/locations/message.not_found'));
        }


        if ($location->users->count() > 0) {
            return Redirect::to('admin/settings/locations')->with('error', Lang::get('admin/locations/message.assoc_users'));
        } elseif ($location->childLocations->count() > 0) {
            return Redirect::to('admin/settings/locations')->with('error', Lang::get('admin/locations/message.assoc_child_loc'));
        } elseif ($location->assets->count() > 0) {
            return Redirect::to('admin/settings/locations')->with('error', Lang::get('admin/locations/message.assoc_assets'));
        } elseif ($location->assignedassets->count() > 0) {
            return Redirect::to('admin/settings/locations')->with('error', Lang::get('admin/locations/message.assoc_assets'));
        } else {
            $location->delete();
            return Redirect::to('admin/settings/locations')->with('success', Lang::get('admin/locations/message.delete.success'));
        }



    }

    public function getDatatable()
    {
        $locations = Location::select(array('id','name','address','address2','city','state','zip','country','parent_id','currency'))->with('assets')
        ->whereNull('deleted_at');

        if (Input::has('search')) {
            $locations = $locations->TextSearch(e(Input::get('search')));
        }

        if (Input::has('offset')) {
            $offset = e(Input::get('offset'));
        } else {
            $offset = 0;
        }

        if (Input::has('limit')) {
            $limit = e(Input::get('limit'));
        } else {
            $limit = 50;
        }

        $allowed_columns = ['id','name','address','city','state','country','currency'];
        $order = Input::get('order') === 'asc' ? 'asc' : 'desc';
        $sort = in_array(Input::get('sort'), $allowed_columns) ? Input::get('sort') : 'created_at';

        $locations->orderBy($sort, $order);

        $locationsCount = $locations->count();
        $locations = $locations->skip($offset)->take($limit)->get();

        $rows = array();

        foreach($locations as $location) {
            $actions = '<a href="'.route('update/location', $location->id).'" class="btn btn-warning btn-sm" style="margin-right:5px;"><i class="fa fa-pencil icon-white"></i></a><a data-html="false" class="btn delete-asset btn-danger btn-sm" data-toggle="modal" href="'.route('delete/location', $location->id).'" data-content="'.Lang::get('admin/locations/message.delete.confirm').'" data-title="'.Lang::get('general.delete').' '.htmlspecialchars($location->name).'?" onClick="return false;"><i class="fa fa-trash icon-white"></i></a>';

            $rows[] = array(
                'id'            => $location->id,
                'name'          => link_to('admin/locations/'.$location->id.'/view', $location->name),
                'parent'        => ($location->parent) ? $location->parent->name : '',
                'assets'        => ($location->assets->count() + $location->assignedassets->count()),
                'address'       => ($location->address) ? $location->address: '',
                'city'          => $location->city,
                'state'         => $location->state,
                'country'       => $location->country,
                'currency'      => $location->currency,
                'actions'       => $actions
            );
        }

        $data = array('total' => $locationsCount, 'rows' => $rows);

        return $data;

    }



}
