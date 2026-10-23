# Lab: PHP2Shell — CVE-2026-109618 / CVE-2026-109619

> **For the Ethical Hacking class demo.** One VM, deliberately unpatched, on an
> isolated host-only network. Read [Safety and scope](#safety-and-scope) before
> you use it.

## What this is

On **October 21, 2026** the PHP project disclosed two critical, unauthenticated,
pre-auth RCE vulnerabilities affecting *every* PHP installation from PHP 5
onward:

- **CVE-2026-109618** — RCE via out-of-memory (OOM) corruption. PHP uses
  `longjmp()` to abandon a request when it runs out of memory; in some code
  paths, request memory is reachable *before* it is fully initialized, so the
  abrupt exit leaves it in a half-built state that shutdown code later trusts.
  A multipart/form-data upload with no usable `Content-Length`, unlimited
  part headers, and empty file fields (which don't count against
  `max_file_uploads`) lets an attacker reach PHP's `memory_limit` deliberately.
- **CVE-2026-109619** — RCE via stale global state (use-after-free). Per-request
  global state (sessions, error state, extension state) can outlive the
  request's own memory. A fatal error (OOM, a timeout, or — the confirmed path
  on PHP 8.5 — `fatal_error_backtraces`) skips the normal reset, and the
  **next** request handled by the same worker can read or free that now-freed
  memory.

Both are rated **CVSS v4.0 9.2 (Critical)**, exploitable **without
authentication**, and rated by PHP's own advisory as most exploitable on
**Apache + `mod_php`** — exactly the stack this lab builds. The industry name
for the pair is **"PHP2Shell"** (coined by Wiz Research, who discovered and
reported both bugs in August 2026).

Patched versions: **PHP 8.5.12, 8.4.27, 8.3.36, 8.2.35** (Oct 21, 2026).
PHP 8.1 and earlier are end-of-life and receive **no official patch** — only
minimal source backports for 7.4/8.0/8.1, or whatever a Linux distro chooses
to backport.

## Learning objective

Students are **not** handed "this is vulnerable, here's the flag." They are
handed a web server and must work out, in order:

1. **Fingerprint it.** What is actually running here, and does the banner tell
   the whole story?
2. **Decide if it's vulnerable.** Banner says PHP 8.5.4 — is that in the
   affected range? (Yes: every 8.5.x before 8.5.12.) But also: *can a version
   banner be trusted at all* for this specific pair of CVEs? (No — see below.)
3. **Decide if it's actually exploitable**, as opposed to merely "an old
   version is installed." These two things are not the same, and the public
   reporting on this event disagrees with itself on exactly that point — which
   is the real lesson.
4. **Apply the fix and show the difference it does and doesn't make** to what
   an external scan can see.

That gap between "vulnerable version" and "exploitable right now" is the
single most important, and most realistic, takeaway here. It is also why this
event is such a good teaching moment: it is playing out in public, in real
time, with security vendors publishing contradictory conclusions from the same
facts.

## The target

- Ubuntu 26.04 LTS, Apache 2.4 + `mod_php`, pinned to the Ubuntu archive build
  that predates the Oct 21 security update: `libapache2-mod-php8.5
  8.5.4-0ubuntu1` (the fix shipped as `8.5.4-0ubuntu1.5`, Ubuntu advisory
  USN-8948-1).
- A small fictional site, **"Harborline Coffee Roasters"** — home, about,
  product catalog (session-based cart), and a **contact form with a file
  attachment field** (`multipart/form-data` — the exact request shape both
  CVEs are triggered through).
- `expose_php = On` (the common default) — `X-Powered-By: PHP/8.5.4` is sent
  on every response.
- A forgotten `info.php` (`phpinfo()`) — a classic, very common recon find
  that also happens to be the easiest way to confirm the exact PHP build.
- `session.upload_progress.enabled` left at its **default (On)** — the
  confirmed trigger path for CVE-2026-109619 on `mod_php`.

All content is fictional; a comment at the top of each PHP file says so.

## Running it

```bash
cd labs/php2shell
vagrant up
```

Site: **http://192.168.64.10/** (host-only network; reachable from the host
and from any other VM on the same VirtualBox host-only adapter, e.g. a Kali
attack box).

Apply the real-world fix (for the "patch and re-verify" half of the exercise):

```bash
vagrant provision --provision-with patch
```

This upgrades to `libapache2-mod-php8.5 8.5.4-0ubuntu1.5` and restarts Apache
(restarting **all** workers matters — a worker that was already running keeps
the old code in memory even after the package is upgraded). Note for the
discussion afterward: `php -v` and the `X-Powered-By` header **still say
`8.5.4`** after the patch — the upstream PHP version string doesn't change,
only the distro package revision does. Ask students how they'd tell a patched
8.5.4 from a vulnerable 8.5.4 from the outside.

To return to the vulnerable state for the next group: `vagrant provision`
(re-runs the default `shell` provisioner, which re-pins the old package).

## Is there a Metasploit module?

**Not yet, as of this writing (Oct 22, 2026, the day after disclosure).**
Rapid7's tracker has an open feature-request issue
([rapid7/metasploit-framework#22034](https://github.com/rapid7/metasploit-framework/issues/22034))
and a linked **draft** pull request
([#22036](https://github.com/rapid7/metasploit-framework/pull/22036)) with a
work-in-progress exploit module for both CVEs — still under review, not
merged, and the author's own comment flags that the reference setup needs
"single-worker calibration," i.e. it isn't a point-and-click module yet.

## Is there a public exploit script?

Yes — several, published within hours to days of disclosure, which is itself
worth discussing (see below). None of them are included in this lab; **do not
run unvetted exploit code from the internet against anything**, read the
source first, and only ever point it at this isolated VM. At minimum, be
aware of:

- **`jackalsyn` PoC** (the first public one) — the author's own write-up calls
  it unreliable, says the memory-corruption stage needs glibc-version-specific
  heap grooming they could not reproduce reliably, and **retracted the "RCE"
  framing**, rating the confirmed, reproducible effect a CVSS 5.3
  crash/denial-of-service, not code execution — while the page's own headline
  and download section still call it "weaponized." A good example of a PoC's
  marketing outrunning its own findings.
- Several `php2shell*` repos (authors including `murrez`, `ikow`, `xuehuolll`,
  `Whackermelon`, `eness`) that implement passive version fingerprinting plus
  an active trigger for CVE-2026-109618, and a build-offset-dependent chain for
  CVE-2026-109619 that its own README describes as not portable across builds.

**The honest state of public exploitation, as reported by named security
vendors, is contradictory — make this the centerpiece of the classroom
discussion, not a footnote:**

| Source | Claim |
|---|---|
| PHP's own advisory | Full RCE demonstrated over HTTP against `mod_php` with **default** settings. |
| [Sansec](https://sansec.io/research/php2shell) | Public exploits **do not** work against a default install; most need the target app to already have an unrelated local-file-inclusion bug. Mass exploitation "less likely than first expected." |
| [watchTowr](https://watchtowr.com/intelligence/php-remote-code-execution-php2shell/) | Observed **active exploitation in the wild** of both CVEs via its honeypot network, starting the same day as disclosure. |
| [Bitdefender](https://www.bitdefender.com/en-us/blog/businessinsights/technical-advisory-php2shell-pre-auth-rce) | No exploitation in its own telemetry as of Oct 22; cites a separate Datadog report of scanning from AWS since Oct 17 (four days *before* public disclosure); notes some circulating PoCs are reportedly non-functional pranks. |

Ask students: how do you reconcile a vendor's "we proved RCE" with another
vendor's "the public exploits don't actually work"? (Answer: both can be true
— a *researcher-demonstrated* exploit chain under controlled conditions is not
the same thing as a *portable, pre-built* public PoC working against an
arbitrary target's exact glibc/ASLR/build. This is the gap between "CVE
exists and is rated 9.2" and "I, with this PoC, can pop this box.")

## Detecting it without exploiting it

Reliable, safe things students can check against this lab:

- `X-Powered-By` header / `phpinfo()` banner → version, **but note it can be
  masked by a CDN, and a distro backport fixes the bug without changing the
  version string at all** — exactly what `vagrant provision --provision-with
  patch` demonstrates above.
- `apachectl -M | grep php` / `dpkg -l | grep php8.5` from a shell on the box
  — the only way to see the *real* package revision, which is what actually
  matters here, not the PHP version number.
- Whether `session.upload_progress.enabled` is on (default) — ask students to
  find this from `phpinfo()` without being told where to look.

## Impact: why this matters beyond one lab

- **PHP powers roughly 70% of websites with a known server-side language**
  (per Bitdefender's mid-2026 figure), and WordPress alone — built on PHP —
  powers over 43% of all websites. These two CVEs affect **every PHP version
  since PHP 5.4**, i.e. essentially the entire installed base, not a niche
  configuration.
- Because the flaw is in **PHP's core memory management**, not in any
  application's code, patching WordPress, Drupal, Joomla, or a custom app does
  **nothing** — only patching (or correctly mitigating) PHP itself helps. Wiz
  demonstrated working chains against WordPress, Drupal, and Joomla container
  images using the *same* underlying bug.
- **No complete mitigation exists short of patching.** PHP's own advisory says
  so explicitly. The interim mitigations (`session.upload_progress.enabled=0`,
  `memory_limit=-1`, `fatal_error_backtraces=0` on 8.5, request-size limits at
  a WAF/proxy) only close the *specific confirmed* trigger paths — they do not
  make an unpatched install safe, and PHP's team says so in writing.
- **PHP 7.4/8.0/8.1 are end-of-life** and get no official fix — only partial
  source backports. A large, usually invisible population of legacy
  WordPress/Drupal sites, internal admin tools, and vendor appliances sit
  here indefinitely. Cisco, OPNsense, pfSense, and Palo Alto Networks all
  issued their own bulletins because they ship PHP inside network appliances
  — this is not just "web hosting's problem."
- **The disclosure itself leaked messily**: an early partial leak exposed the
  CVE IDs with a wrong CVSS score (10.0 instead of the correct 9.2); a
  researcher accidentally published a PoC video against real targets before
  the fix shipped; a container-image vendor (Chainguard) pushed patched images
  ~6 hours before the public advisory, letting anyone diff the binary fix
  early. All of this compressed the "patch gap" — the window between patch
  availability and functional public exploits — to almost nothing, which is
  itself a trend worth discussing: coordinated disclosure increasingly leaks
  before the coordinated date.
- **AI-assisted vulnerability research found this.** Wiz says it discovered
  the bugs using its own AI tooling, and the PHP Foundation's own write-up
  says it now uses AI tools in its own security scanning and patch
  validation. Worth 5 minutes of class discussion: the same capability that
  helps defenders find bugs faster also compresses the time attackers need to
  weaponize them.

## Safety and scope

- **Isolated host-only network only** (`192.168.64.10`, VirtualBox
  `hostonly` adapter). Do not bridge this VM onto any other network.
- The VM is **intentionally vulnerable**. Treat it like any other
  intentionally-vulnerable lab target: don't leave it running outside of
  class, don't expose it to the internet, don't reuse the box for anything
  else.
- If you bring public PoC code into class, **read it before running it** —
  several circulating repos for this exact CVE pair have already been
  reported as non-functional or mislabeled (see the `jackalsyn` retraction
  above). That caution is itself part of the lesson.

## Sources

- PHP Foundation security release: <https://thephp.foundation/blog/2026/10/21/todays-php-security-release-and-how-were-strengthening-php-security/>
- PHP advisory archive: <https://www.php.net/archive/2026.php#2026-10-20-1>
- GHSA-w8hw-w9fx-j79w (CVE-2026-109618): <https://github.com/php/php-src/security/advisories/GHSA-w8hw-w9fx-j79w>
- GHSA-6wmf-4rv5-p3rp (CVE-2026-109619): <https://github.com/php/php-src/security/advisories/GHSA-6wmf-4rv5-p3rp>
- Ubuntu USN-8948-1: <https://ubuntu.com/security/notices/USN-8948-1>
- Wiz Research, "PHP2Shell": <https://www.wiz.io/blog/php2shell-php-rce>
- Sansec: <https://sansec.io/research/php2shell>
- watchTowr: <https://watchtowr.com/intelligence/php-remote-code-execution-php2shell/>
- Bitdefender: <https://www.bitdefender.com/en-us/blog/businessinsights/technical-advisory-php2shell-pre-auth-rce>
- SANS ISC diary: <https://isc.sans.edu/diary/Critical+PHP+Vulnerability+CVE2026109618+CVE2026109619+infocon+yellow/33440/>
- Metasploit tracking issue: <https://github.com/rapid7/metasploit-framework/issues/22034>
