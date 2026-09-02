<footer id="footer" class="bg-[#191615] text-white pt-12 pb-5 motion-section">
  <div class="max-w-[1220px] mx-auto px-6 sm:px-8 lg:px-12 xl:px-16">
    <div class="grid grid-cols-1 lg:grid-cols-[0.95fr_1.35fr] gap-8 lg:gap-12 items-center pb-9 border-b border-white/10 motion-item motion-soft-up">
      <a href="{{ route('home') }}" class="inline-flex items-center">
        <img src="{{ asset('assets/logo.png') }}" alt="CoachNow Logo" class="h-10 lg:h-11 w-auto object-contain">
      </a>
      <div class="flex flex-col sm:flex-row items-start sm:items-center gap-4 sm:gap-5 lg:justify-end">
        <div class="text-[14px] lg:text-[15px] font-semibold leading-[1.55] shrink-0">Stay Connected<br>With CoachNow</div>
        <form onsubmit="return false;" class="flex w-full sm:w-[470px] gap-3">
          <input type="email" placeholder="Enter Your E-mail Address *" class="min-w-0 flex-1 h-11 rounded-[7px] bg-[#302D2D] border border-white/5 px-4 text-[12px] lg:text-[13px] text-white placeholder:text-zinc-300 outline-none focus:border-brand-red transition-colors">
          <button type="submit" class="h-11 px-8 rounded-[7px] bg-brand-red hover:bg-brand-red-hover text-[12px] lg:text-[13px] font-medium transition-colors">Subscribe</button>
        </form>
      </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-[1.3fr_.78fr_.78fr_1.12fr] gap-0 border-b border-white/10">
      <div class="py-10 lg:pr-10 lg:border-r border-white/10 motion-item motion-soft-up" style="--motion-delay:100ms">
        <p class="text-[12px] lg:text-[13px] text-zinc-400 leading-[1.85] max-w-[320px]">CoachNow helps athletes and families discover trusted local coaches, compare training options, and book sessions that fit their goals and schedules.</p>
        <div class="flex gap-2.5 mt-6">
          <a href="#" aria-label="Facebook" class="w-8 h-8 rounded-[7px] border border-zinc-600 text-white flex items-center justify-center text-[13px] font-semibold hover:bg-brand-red hover:border-brand-red transition-all">f</a>
          <a href="#" aria-label="X" class="w-8 h-8 rounded-[7px] border border-zinc-600 text-white flex items-center justify-center text-[12px] hover:bg-brand-red hover:border-brand-red transition-all">𝕏</a>
          <a href="#" aria-label="LinkedIn" class="w-8 h-8 rounded-[7px] border border-zinc-600 text-white flex items-center justify-center text-[12px] font-semibold hover:bg-brand-red hover:border-brand-red transition-all">in</a>
          <a href="#" aria-label="Instagram" class="w-8 h-8 rounded-[7px] border border-zinc-600 text-white flex items-center justify-center hover:bg-brand-red hover:border-brand-red transition-all"><svg viewBox="0 0 24 24" class="w-[13px] h-[13px] fill-none stroke-current stroke-[1.8]"><rect x="3.5" y="3.5" width="17" height="17" rx="4"></rect><circle cx="12" cy="12" r="4"></circle><circle cx="17.2" cy="6.8" r="1.2" fill="currentColor" stroke="none"></circle></svg></a>
        </div>
      </div>
      <div class="py-10 lg:px-10 lg:border-r border-white/10 motion-item motion-soft-up" style="--motion-delay:185ms">
        <h4 class="text-[14px] lg:text-[15px] font-semibold mb-5">Explore</h4>
        <ul class="space-y-3 text-[12px] lg:text-[13px] text-zinc-400">
          <li><a href="{{ route('find-a-coach') }}" class="hover:text-white transition-colors">Find a Coach</a></li>
          <li><a href="{{ route('request-session') }}" class="hover:text-white transition-colors">Request Session</a></li>
          <li><a href="{{ route('player-dashboard') }}" class="hover:text-white transition-colors">Player Dashboard</a></li>
          <li><a href="{{ route('home') }}#how-it-works" class="hover:text-white transition-colors">How It Works</a></li>
          <li><a href="{{ route('home') }}#training" class="hover:text-white transition-colors">Training Options</a></li>
          <li><a href="{{ route('become-a-coach') }}" class="hover:text-white transition-colors">Become a Coach</a></li>
          <li><a href="{{ route('become-a-coach') }}" class="hover:text-white transition-colors">Founding Coaches</a></li>
        </ul>
      </div>
      <div class="py-10 lg:px-10 lg:border-r border-white/10 motion-item motion-soft-up" style="--motion-delay:270ms">
        <h4 class="text-[14px] lg:text-[15px] font-semibold mb-5">Company</h4>
        <ul class="space-y-3 text-[12px] lg:text-[13px] text-zinc-400">
          <li><a href="{{ route('about') }}" class="hover:text-white transition-colors">About CoachNow</a></li>
          <li><a href="{{ route('contact') }}" class="hover:text-white transition-colors">Contact</a></li>
          <li><a href="{{ route('faq') }}" class="hover:text-white transition-colors">FAQ</a></li>
          <li><a href="#" class="hover:text-white transition-colors">Privacy Policy</a></li>
          <li><a href="#" class="hover:text-white transition-colors">Cookie Policy</a></li>
        </ul>
      </div>
      <div class="py-10 lg:pl-10 motion-item motion-soft-up" style="--motion-delay:355ms">
        <h4 class="text-[14px] lg:text-[15px] font-semibold mb-5">Contact Info</h4>
        <div class="text-[13px] lg:text-[14px] text-white">
          <div class="flex items-center gap-3 pb-4 border-b border-white/10"><span class="w-9 h-9 rounded-[8px] bg-brand-red flex items-center justify-center shrink-0"><svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6A19.79 19.79 0 0 1 2.12 4.18 2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.12.9.33 1.79.62 2.64a2 2 0 0 1-.45 2.11L8 9.73a16 16 0 0 0 6 6l1.26-1.26a2 2 0 0 1 2.11-.45c.85.29 1.74.5 2.64.62A2 2 0 0 1 22 16.92z"/></svg></span><span>(782) 444-6566</span></div>
          <div class="flex items-center gap-3 py-4 border-b border-white/10"><span class="w-9 h-9 rounded-[8px] bg-brand-red flex items-center justify-center shrink-0"><svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3 7 9 6 9-6"/></svg></span><span>support@coachnow.com</span></div>
          <div class="flex items-start gap-3 pt-4"><span class="w-9 h-9 rounded-[8px] bg-brand-red flex items-center justify-center shrink-0"><svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M21 10c0 7-9 12-9 12S3 17 3 10a9 9 0 1 1 18 0z"/><circle cx="12" cy="10" r="2.5"/></svg></span><span class="leading-[1.2]">Murrieta &amp; Temecula,<br>California</span></div>
        </div>
      </div>
    </div>

    <div class="pt-6 text-center text-[11px] lg:text-[12px] text-zinc-300 motion-item motion-soft-up" style="--motion-delay:440ms">
      © 2026 <span class="text-brand-red font-medium">CoachNow.</span> All Rights Reserved. Design by <a href="https://texaswebstudio.co/" target="_blank" rel="noopener noreferrer" class="text-brand-red font-medium hover:underline">Texas Web Studio</a>
    </div>
  </div>
</footer>
