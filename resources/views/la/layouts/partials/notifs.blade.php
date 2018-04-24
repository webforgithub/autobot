<!-- Navbar Right Menu -->
<div class="navbar-custom-menu">
    <ul class="nav navbar-nav">
       
        @if (Auth::guest())
        <li><a href="{{ url('/login') }}">Login</a></li>
        <li><a href="{{ url('/register') }}">Register</a></li>
        @else
        <!-- User Account Menu -->
        <li class="dropdown user user-menu">
            <!-- Menu Toggle Button -->
            <a href="#" class="dropdown-toggle" data-toggle="dropdown">
                <!-- The user image in the navbar-->
                <img src="{{ Gravatar::fallback(asset('la-assets/img/user2-160x160.jpg'))->get(Auth::user()->email) }}" class="user-image" alt="User Image"/>
                <!-- hidden-xs hides the username on small devices so only the image appears. -->
                <span class="hidden-xs">{{ Auth::user()->name }}</span>
            </a>
            <ul class="dropdown-menu">
                <!-- The user image in the menu -->
                <li class="user-header">
                    <img src="{{ Gravatar::fallback(asset('la-assets/img/user2-160x160.jpg'))->get(Auth::user()->email) }}" class="img-circle" alt="User Image" />
                    <p>
                        {{ Auth::user()->name }}
                        <?php
                        $datec = Auth::user()['created_at'];
                        ?>
                        <small>Member since <?php echo date("M. Y", strtotime($datec)); ?></small>
                    </p>
                </li>
                <!-- Menu Body -->
                @role("SUPER_ADMIN")
                <li class="user-body">
                    <div class="col-xs-6 text-center mb10">
                        <a href="{{ url(config('laraadmin.adminRoute') . '/lacodeeditor') }}"><i class="fa fa-code"></i> <span>Editor</span></a>
                    </div>
                    <div class="col-xs-6 text-center mb10">
                        <a href="{{ url(config('laraadmin.adminRoute') . '/modules') }}"><i class="fa fa-cubes"></i> <span>Modules</span></a>
                    </div>
                    <div class="col-xs-6 text-center mb10">
                        <a href="{{ url(config('laraadmin.adminRoute') . '/la_menus') }}"><i class="fa fa-bars"></i> <span>Menus</span></a>
                    </div>
                    <div class="col-xs-6 text-center mb10">
                        <a href="{{ url(config('laraadmin.adminRoute') . '/la_configs') }}"><i class="fa fa-cogs"></i> <span>Configure</span></a>
                    </div>
                    <div class="col-xs-6 text-center">
                        <a href="{{ url(config('laraadmin.adminRoute') . '/backups') }}"><i class="fa fa-hdd-o"></i> <span>Backups</span></a>
                    </div>
                </li>
                @endrole
                <!-- Menu Footer-->
                <li class="user-footer">
                    <div class="pull-left">
                        <a href="{{ url(config('laraadmin.adminRoute') . '/users/') .'/'. Auth::user()->id }}" class="btn btn-default btn-flat">Profile</a>
                    </div>
                    <div class="pull-right">
                        <a href="{{ url('/logout') }}" class="btn btn-default btn-flat">Sign out</a>
                    </div>
                </li>
            </ul>
        </li>
        @endif        
    </ul>
</div>