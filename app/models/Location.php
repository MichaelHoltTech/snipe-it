<?php

class Location extends Elegant
{
    use SoftDeletingTrait;
    protected $dates = ['deleted_at'];
    protected $table = 'locations';
    protected $rules = array(
            'name'  		=> 'required|alpha_space|min:3|max:255|unique:locations,name,{id}',
            'city'   		=> 'required|alpha_space|min:3|max:255',
            'currency'   		=> 'required',
            'state'   		=> 'alpha_space|min:2|max:32',
            'country'   	=> 'required|alpha_space|min:2|max:2|max:2',
            'address'		=> 'alpha_space|min:5|max:80',
            'address2'		=> 'alpha_space|min:2|max:80',
            'zip'   		=> 'alpha_space|min:3|max:10',
        );

    public function users() {
        return $this->hasMany('User', 'location_id');
    }

    public function assets() {
        return $this->hasMany('Actionlog','location_id');
    }

    public function assignedassets() {
        return $this->hasMany('Asset','rtd_location_id');
    }

    public function parent() {
        return $this->belongsTo('Location', 'parent_id');
    }

    public function childLocations() {
        return $this->hasMany('Location','parent_id');
    }

    public static function getLocationHierarchy($locations, $parent_id = null) {


        $op = array();

        foreach($locations as $location) {

            if ($location['parent_id'] == $parent_id) {
                $op[$location['id']] =
                    array(
                        'name' => $location['name'],
                        'parent_id' => $location['parent_id']
                    );

                // Using recursion
                $children =  Location::getLocationHierarchy($locations, $location['id']);
                if ($children) {
                    $op[$location['id']]['children'] = $children;
                }

            }

        }
        return $op;
    }


    public static function flattenLocationsArray ($location_options_array = null) {
        $location_options = array();
        foreach ($location_options_array as $id => $value) {

            // get the top level key value
            $location_options[$id] = $value['name'];

                // If there is a key named children, it has child locations and we have to walk it
                if (array_key_exists('children',$value)) {

                    foreach ($value['children'] as $child_id => $child_location_array) {
                        $child_location_options = Location::flattenLocationsArray($value['children']);

                        foreach ($child_location_options as $child_id => $child_name) {
                            $location_options[$child_id] = '--'.$child_name;
                        }
                    }

                }

        }

        return $location_options;
    }

    /**
    * Query builder scope to search on text
    *
    * @param  Illuminate\Database\Query\Builder  $query  Query builder instance
    * @param  text                              $search    	 Search term
    *
    * @return Illuminate\Database\Query\Builder          Modified query builder
    */
    public function scopeTextsearch($query, $search)
    {

            return $query->where('name', 'LIKE', "%$search%")
                ->orWhere('address', 'LIKE', "%$search%")
                ->orWhere('city', 'LIKE', "%$search%")
                ->orWhere('state', 'LIKE', "%$search%")
                ->orWhere('zip', 'LIKE', "%$search%")
                
                // This doesn't actually work - need to use a table alias maybe?
                ->orWhere(function($query) use ($search) {
                    $query->whereHas('parent', function($query) use ($search) {
                        $query->where(function($query) use ($search) {
                            $query->where('name','LIKE','%'.$search.'%');
                        });
                    });
                });

    }


}
