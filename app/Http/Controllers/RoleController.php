<?php


namespace App\Http\Controllers;


use App\Http\Requests\StoreRoleRequest;
use App\Http\Requests\UpdateRoleRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;


class RoleController extends Controller
{
   public function __construct()
   {
       $this->middleware('auth');
       $this->middleware('permission:create-role|edit-role|delete-role', ['only' => ['index', 'show']]);
       $this->middleware('permission:create-role', ['only' => ['create', 'store']]);
       $this->middleware('permission:edit-role', ['only' => ['edit', 'update']]);
       $this->middleware('permission:delete-role', ['only' => ['destroy']]);


   }
   /**
    * Display a listing of the resource.
    */
   public function index()
   {
       return view('roles.index',
       [
           'roles' => Role::orderBy('id', 'DESC')->paginate(3),
       ]);
   }


   /**
    * Show the form for creating a new resource.
    */
   public function create()
   {
       return view('roles.create',[
           'permissions' => Permission::get(),
       ]);
   }


   /**
    * Store a newly created resource in storage.
    */
   public function store(StoreRoleRequest $request) : RedirectResponse
   {
       $role = Role::create(['name' => $request->name]);
       $permissions = Permission::whereIn('id', $request->permissions)->get(['name'])->toArray();
       $role->syncPermissions($permissions);


       return redirect()->route('roles.index')->withSuccess('New role is added successfully');
   }


   /**
    * Display the specified resource.
    */
   public function show(Role $role)
   {
       $rolePermissions = Permission::join('role_has_permissions', 'permissions.id', '=', 'id')
           ->where('role_id', $role->id)
           ->select('name')
           ->get();
       return view('roles.show', [
           'role' => $role,
           'rolePermissions' => $rolePermissions,
       ]);
   }


   /**
    * Show the form for editing the specified resource.
    */
   public function edit(Role $role)
   {
       if($role->name === 'admin baak'){
           abort(403, 'Unauthorized action');
       }


       $rolePermissions =DB::table('role_has_permissions')
           ->where('role_id', $role->id)
           ->pluck('permission_id')
           ->all();


       return view('roles.edit', [
           'role' => $role,
           'permissions' => Permission::get(),
           'rolePermissions' => $rolePermissions,
       ]);
   }


   /**
    * Update the specified resource in storage.
    */
   public function update(UpdateRoleRequest $request, Role $role) : RedirectResponse
   {
      $input = $request->only('name');
      $role->update($input);
      $permissions = Permission::whereIn('id', $request->permissions)->get(['name'])->toArray();
      $role->syncPermissions($permissions);
      return redirect()->route('roles.index')->withSuccess('Role is updated successfully');
   }


   /**
    * Remove the specified resource from storage.
    */
   public function destroy(Role $role) : RedirectResponse
   {
       if($role->name === 'adminbaak'){
           abort(403, 'Unauthorized action');
       }
       if(auth()->user()->hasRole($role->name)){
           abort(403, 'Unauthorized action');
       }


       $role->delete();
       return redirect()->route('roles.index')->withSuccess('Role is deleted successfully');
   }
}
