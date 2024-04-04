<header class="bg-green-700 text-white sticky top-0 z-20">
    <section class="max-w-6xl mx-auto p-2 flex md:justify-between justify-center items-center">
        <div class="flex items-center justify-between border-accent gap-1">
            <div class="avatar">
                <div class="w-16 rounded">
                    <img src="assets/cvsulogo-removebg-preview.png" />
                </div>
            </div>
            <div class="text-center font-bold text-black text-3xl">
                <h1><a href="#" id="homeLink">REPOSYNC</a></h1>
            </div>
        </div>

        <button id="mobile-open-button" class="text-3xl md:hidden absolute left-0 focus:outline-none" onclick="toggleNav()">
            &#9776;
        </button>
        <div>
            <nav class="md:block hidden space-x-8 text-lg/normal md:text-xl flex flex-wrap" aria-label="main">
                <a href="#" id="narrativesLink" class="font-bold hover:cursor-pointer hover:opacity-75">Narrative Reports</a>
                <a href="#" id="announcementLink" class="font-bold hover:cursor-pointer hover:opacity-75">Announcement</a>
                <a href="#" id="announcementLink" class="font-bold hover:cursor-pointer hover:opacity-75">Sign Up</a>
                <div class="relative inline-block ">
                    <button onclick="myFunction()" class="btn btn-success text-xl">Login<i class="fa-solid fa-caret-down"></i></button>
                    <div id="myDropdown" class=" transition-all rounded border right-0
                    hidden text-center absolute bg-gray-600 w-52 shadow text-black">
                        <div class="bg-slate-50 h-10 hover:opacity-75 p-3  border flex justify-center items-center">
                            <a href="">Student or Adviser</a>
                        </div>
                        <div class="bg-slate-50 h-10 hover:opacity-75 p-3   flex justify-center items-center">
                            <a href="">Admin</a>
                        </div>
                    </div>
                </div>

            </nav>

        </div>
    </section>
</header>
<div id="mySidenav" class=" sm:hidden fixed top-0 left-0 h-full w-1/2 bg-gray-600  text-white  transition-transform transform translate-x-[-100%] z-30">
    <div onclick="closeNav()" class="rounded h-6 w-6 foc absolute active:border  right-0">
        <a href="#" class="grid place-items-center mt-1"><i class="fas fa-times"></i></a>
    </div>
    <section class="sm:hidden mt-10">
        <div class="flex flex-col text-center">
            <div class="h-16 hover:bg-gray-200 transition-all rounded">
                <h1 class="mt-6 text-3xl font-bold hover:opacity-90"><a href="index.php?page=home">Home</a></h1>
                <hr class="mx-auto  bg-black  w-[80%]">
            </div>
            <div class="h-16 hover:bg-gray-200 transition-all rounded">
                <h1 class="text-3xl font-bold mt-6 hover:opacity-90"><a href="menu.php">Menu</a></h1>
                <hr class="mx-auto bg-black  w-[80%]">
            </div>
            <div class="h-16 hover:bg-gray-200 transition-all rounded">
                <h1 class="text-3xl font-bold mt-6 hover:opacity-90"><a href="index.php?page=login">Login</a></h1>
                <hr class="mx-auto bg-black  w-[80%]">
            </div>
        </div>
    </section>
</div>
