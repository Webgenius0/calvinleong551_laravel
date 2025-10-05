<!-- section about -->
<section id="about" class="{{ isset($cms_about->is_display) && $cms_about->is_display == false ? 'd-none' : '' }}">

    <div class="container">

        <!-- section title -->
        <h2 class="section-title wow fadeInUp">About Me</h2>

        <div class="spacer" data-height="60"></div>

        <div class="row">

            <div class="col-md-3">
                <div class="text-center text-md-left">
                    <!-- avatar image -->
                    <img src="{{ asset($cms_about->image ?? 'default/profile.jpg') }}" alt="Bolby" class="img-thumbnail" style="width:150px; height: 180px;" />
                </div>
                <div class="spacer d-md-none d-lg-none" data-height="30"></div>
            </div>

            <div class="col-md-9 triangle-left-md triangle-top-sm">
                <div class="rounded bg-white shadow-dark padding-30">
                    <div class="row">
                        <div class="col-md-6">
                            <!-- about text -->
                            <p>I am a passionate and driven software developer with a strong ability to quickly learn and adapt to new technologies. I am seeking a dynamic and collaborative team where I can leverage my software development experience, problem-solving skills, and analytical mindset to make impactful contributions. I am highly self-motivated, able to work independently or as part of a team, and always eager to embrace new challenges and expand my skill set. My goal is to continuously grow and deliver value to both the organization and my career.</p>
                            <div class="mt-3">
                                <a href="#" class="btn btn-default">Download CV</a>
                            </div>
                            <div class="spacer d-md-none d-lg-none" data-height="30"></div>
                        </div>
                        <div class="col-md-6">
                            <!-- skill item -->
                            <div class="skill-item">
                                <div class="skill-info clearfix">
                                    <h4 class="float-left mb-3 mt-0">Web Design</h4>
                                    <span class="float-right">85%</span>
                                </div>
                                <div class="progress">
                                    <div class="progress-bar data-background" role="progressbar"
                                        aria-valuemin="0" aria-valuemax="100" aria-valuenow="85"
                                        data-color="#FFD15C">
                                    </div>
                                </div>
                                <div class="spacer" data-height="20"></div>
                            </div>

                            <!-- skill item -->
                            <div class="skill-item">
                                <div class="skill-info clearfix">
                                    <h4 class="float-left mb-3 mt-0">Web Development</h4>
                                    <span class="float-right">95%</span>
                                </div>
                                <div class="progress">
                                    <div class="progress-bar data-background" role="progressbar"
                                        aria-valuemin="0" aria-valuemax="100" aria-valuenow="95"
                                        data-color="#FF4C60">
                                    </div>
                                </div>
                                <div class="spacer" data-height="20"></div>
                            </div>

                            <!-- skill item -->
                            <div class="skill-item">
                                <div class="skill-info clearfix">
                                    <h4 class="float-left mb-3 mt-0">Deployment</h4>
                                    <span class="float-right">70%</span>
                                </div>
                                <div class="progress">
                                    <div class="progress-bar data-background" role="progressbar"
                                        aria-valuemin="0" aria-valuemax="100" aria-valuenow="70"
                                        data-color="#6C6CE5">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
        <!-- row end -->
        <div class="spacer" data-height="70"></div>

        @include('frontend.layouts.home.sections.marquee')

        <div class="spacer" data-height="60"></div>

        <div class="row text-center">

            <div class="col-md-6 col-sm-6">
                <!-- fact item -->
                <div class="fact-item">
                    <div class="details">
                        <i class="fa-solid fa-sheet-plastic" style="font-size: 30px;"></i>
                        <h3 class="mb-0 mt-0 number"><em class="count">30</em></h3>
                        <p class="mb-0">Projects completed</p>
                    </div>
                </div>
                <div class="spacer d-md-none d-lg-none" data-height="30"></div>
            </div>

            <div class="col-md-6 col-sm-6">
                <!-- fact item -->
                <div class="fact-item">
                    <div class="details">
                        <i class="fa-solid fa-person" style="font-size: 30px;"></i>
                        <h3 class="mb-0 mt-0 number"><em class="count">15</em></h3>
                        <p class="mb-0">Satisfied clients</p>
                    </div>
                </div>
                <div class="spacer d-md-none d-lg-none" data-height="30"></div>
            </div>

        </div>

    </div>

    </div>

</section>