<style>
      #load{
    /*display: none;*/
    width: 100%;
    height: 100%;
    position:fixed;
    z-index:9999;
    background:url("images/logo.svg") no-repeat center center rgba(0,0,0,0.25)
}
    </style>
<body class="sidebar-light">
        <div class="container-scroller">
            <!-- partial:partials/_navbar.html -->
            <nav class="navbar col-lg-12 col-12 p-0 fixed-top d-flex flex-row default-layout-navbar">
                <div class="text-center navbar-brand-wrapper d-flex align-items-center justify-content-center">
                    <a class="navbar-brand brand-logo" href="https://srishringarr.com/pos">
                        <img alt="logo" src="/pos/images/logo.png"/>
                    </a>
                    <a class="navbar-brand brand-logo-mini" href="https://srishringarr.com/pos">
                        <img alt="logo" src="/pos/images/logo.png"/>
                    </a>
                </div>
                <div class="navbar-menu-wrapper d-flex align-items-stretch">
                    <button class="navbar-toggler navbar-toggler align-self-center" data-toggle="minimize" type="button">
                        <span class="fas fa-bars">
                        </span>
                    </button>
                    <ul class="navbar-nav navbar-nav-left">
                        <li class="nav-item nav-search d-none d-md-flex">
                            <div class="nav-link">
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text">
                                            <i class="fas fa-search">
                                            </i>
                                        </span>
                                    </div>
                                    <input aria-label="Search" class="form-control" placeholder="Search" type="text">
                                    </input>
                                </div>
                            </div>
                        </li>
                    </ul>
                    <ul class="navbar-nav navbar-nav-right">
                        <li class="nav-item d-none d-lg-block">
                            <a class="nav-link" href="#">
                                <i class="fas fa-ellipsis-v">
                                </i>
                            </a>
                        </li>
                    </ul>
					<button class="navbar-toggler navbar-toggler-right d-lg-none align-self-center" type="button" data-toggle="offcanvas">
					  <span class="fas fa-bars"></span>
					</button>
                </div>
            </nav>
            <!-- partial -->
			<div id="load" style="display:none;"></div>