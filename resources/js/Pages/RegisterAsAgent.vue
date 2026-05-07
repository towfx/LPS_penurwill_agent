<template>
  <div class="min-h-screen bg-cream font-sans">
    <div class="container mx-auto px-4 py-8">
      <div class="max-w-4xl mx-auto">
        <!-- Back -->
        <div class="mb-8">
          <a href="/get-started" class="inline-flex items-center text-gold hover:text-amber-700 font-medium transition-colors">
            <ArrowLeft class="w-4 h-4 mr-2" /> Back to Get Started
          </a>
        </div>

        <!-- Header -->
        <div class="text-center mb-8">
          <h1 class="text-3xl lg:text-4xl font-bold text-forest-dark mb-4">Register as Agent</h1>
          <p class="text-lg text-stone-600">Complete your registration to join our network</p>
        </div>

        <!-- Progress Steps -->
        <div class="mb-8">
          <div class="flex items-center justify-center flex-wrap gap-y-3">
            <div v-for="(step, index) in steps" :key="index" class="flex items-center">
              <div
                class="flex items-center justify-center w-10 h-10 rounded-full border-2 transition-colors"
                :class="getStepClasses(index)"
              >
                <CheckCircle v-if="index < currentStep" class="w-5 h-5" />
                <span v-else class="text-sm font-medium">{{ index + 1 }}</span>
              </div>
              <span class="ml-2 text-sm font-medium hidden md:block" :class="getStepLabelClasses(index)">{{ step.label }}</span>
              <div v-if="index < steps.length - 1" class="w-8 lg:w-12 h-0.5 mx-2 lg:mx-3 transition-colors" :class="index < currentStep ? 'bg-gold' : 'bg-stone-300'"></div>
            </div>
          </div>
        </div>

        <!-- Wizard Card -->
        <Card class="p-8">
          <!-- ── STEP 1: Referral ID ── -->
          <div v-if="currentStep === 0" class="space-y-6">
            <div class="text-center mb-6">
              <h2 class="text-2xl font-bold text-forest-dark mb-2">Referral ID</h2>
              <p class="text-stone-600">Do you have a Referral ID from an existing agent?</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <label class="relative cursor-pointer" :class="referral.choice === 'yes' ? 'ring-2 ring-gold rounded-lg' : ''">
                <input v-model="referral.choice" @change="resetReferralValidation" type="radio" value="yes" class="sr-only" />
                <div class="p-4 border rounded-lg transition-all hover:bg-accent-green/5">
                  <div class="flex items-center">
                    <div class="w-5 h-5 border-2 rounded-full mr-3 flex items-center justify-center flex-shrink-0">
                      <div v-if="referral.choice === 'yes'" class="w-3 h-3 bg-gold rounded-full"></div>
                    </div>
                    <div><div class="font-medium text-forest-dark">Yes</div><div class="text-sm text-stone-500">I have a Referral ID</div></div>
                  </div>
                </div>
              </label>
              <label class="relative cursor-pointer" :class="referral.choice === 'no' ? 'ring-2 ring-gold rounded-lg' : ''">
                <input v-model="referral.choice" @change="resetReferralValidation" type="radio" value="no" class="sr-only" />
                <div class="p-4 border rounded-lg transition-all hover:bg-accent-green/5">
                  <div class="flex items-center">
                    <div class="w-5 h-5 border-2 rounded-full mr-3 flex items-center justify-center flex-shrink-0">
                      <div v-if="referral.choice === 'no'" class="w-3 h-3 bg-gold rounded-full"></div>
                    </div>
                    <div><div class="font-medium text-forest-dark">No</div><div class="text-sm text-stone-500">Continue without an upline</div></div>
                  </div>
                </div>
              </label>
            </div>

            <div v-if="referral.choice === 'yes'" class="space-y-3">
              <label class="block text-sm font-medium text-stone-700">Referral ID *</label>
              <div class="flex gap-3">
                <input
                  v-model="referral.code"
                  @input="resetReferralValidation"
                  type="text"
                  class="flex-1 px-4 py-3 border border-stone-300 rounded-lg focus:ring-2 focus:ring-gold focus:border-gold"
                  placeholder="Enter Referral ID"
                />
                <Button type="button" @click="validateReferralCode" :disabled="!referral.code || referral.validating">
                  <span v-if="referral.validating" class="flex items-center gap-2"><Loader2 class="w-4 h-4 animate-spin" /> Validating</span>
                  <span v-else>Validate</span>
                </Button>
              </div>
              <div v-if="referral.status === 'valid'" class="p-4 rounded-lg bg-accent-green/10 border border-accent-green flex items-start gap-3">
                <CheckCircle class="w-5 h-5 text-accent-green flex-shrink-0 mt-0.5" />
                <div><div class="font-medium text-forest-dark">Referral ID verified</div><div class="text-sm text-stone-600">Referring agent: <span class="font-medium">{{ referral.agentName }}</span></div></div>
              </div>
              <div v-if="referral.status === 'invalid'" class="p-4 rounded-lg bg-accent-red/10 border border-accent-red flex items-start gap-3">
                <AlertCircle class="w-5 h-5 text-accent-red flex-shrink-0 mt-0.5" />
                <div class="text-sm text-accent-red font-medium">{{ referral.errorMessage }}</div>
              </div>
            </div>

            <div v-if="referral.choice === 'no'" class="p-4 rounded-lg bg-stone-100 border border-stone-200 text-sm text-stone-700">
              You'll be assigned to the default Business Partner as your upline.
            </div>

            <div class="flex justify-end pt-6">
              <Button type="button" @click="nextStep" :disabled="!canProceedFromStep1">Next →</Button>
            </div>
          </div>

          <!-- ── STEP 2: Package Selection ── -->
          <div v-else-if="currentStep === 1" class="space-y-6">
            <div class="text-center mb-6">
              <h2 class="text-2xl font-bold text-forest-dark mb-2">Select Your Package</h2>
              <p class="text-stone-600">Choose the registration package that suits your needs</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <!-- Agent / Agent Leader -->
              <label
                class="relative cursor-pointer rounded-xl border-2 transition-all"
                :class="pkg.choice === 'agent' ? 'border-gold bg-gold/5' : 'border-stone-200 hover:border-gold/50'"
              >
                <input v-model="pkg.choice" type="radio" value="agent" class="sr-only" />
                <div class="p-6">
                  <div class="flex items-center justify-between mb-4">
                    <div class="w-10 h-10 rounded-full bg-forest-light/20 flex items-center justify-center">
                      <Users class="w-5 h-5 text-forest-light" />
                    </div>
                    <div class="w-5 h-5 rounded-full border-2 flex items-center justify-center" :class="pkg.choice === 'agent' ? 'border-gold' : 'border-stone-300'">
                      <div v-if="pkg.choice === 'agent'" class="w-3 h-3 bg-gold rounded-full"></div>
                    </div>
                  </div>
                  <h3 class="text-lg font-bold text-forest-dark mb-1">Agent / Agent Leader</h3>
                  <p class="text-sm text-stone-500 mb-4">Start as an agent, earn commissions on your sales</p>
                  <div class="text-2xl font-bold text-gold">{{ formatCurrency('RM', entryFeeAgent) }}</div>
                  <div class="text-xs text-stone-400 mt-1">One-time registration fee</div>
                  <ul class="mt-4 space-y-1 text-sm text-stone-600">
                    <li class="flex items-center gap-2"><CheckCircle class="w-4 h-4 text-accent-green flex-shrink-0" /> Individual or company profile</li>
                    <li class="flex items-center gap-2"><CheckCircle class="w-4 h-4 text-accent-green flex-shrink-0" /> Earn own-sales commissions</li>
                    <li class="flex items-center gap-2"><CheckCircle class="w-4 h-4 text-accent-green flex-shrink-0" /> Eligible for role upgrade to Leader</li>
                  </ul>
                </div>
              </label>

              <!-- Business Partner -->
              <label
                class="relative cursor-pointer rounded-xl border-2 transition-all"
                :class="pkg.choice === 'business_partner' ? 'border-gold bg-gold/5' : 'border-stone-200 hover:border-gold/50'"
              >
                <input v-model="pkg.choice" type="radio" value="business_partner" class="sr-only" />
                <div class="p-6">
                  <div class="flex items-center justify-between mb-4">
                    <div class="w-10 h-10 rounded-full bg-gold/20 flex items-center justify-center">
                      <Building2 class="w-5 h-5 text-gold" />
                    </div>
                    <div class="w-5 h-5 rounded-full border-2 flex items-center justify-center" :class="pkg.choice === 'business_partner' ? 'border-gold' : 'border-stone-300'">
                      <div v-if="pkg.choice === 'business_partner'" class="w-3 h-3 bg-gold rounded-full"></div>
                    </div>
                  </div>
                  <h3 class="text-lg font-bold text-forest-dark mb-1">Business Partner</h3>
                  <p class="text-sm text-stone-500 mb-4">Build and manage a team of agents</p>
                  <div class="text-2xl font-bold text-gold">{{ formatCurrency('RM', entryFeeBusinessPartner) }}</div>
                  <div class="text-xs text-stone-400 mt-1">One-time registration fee</div>
                  <ul class="mt-4 space-y-1 text-sm text-stone-600">
                    <li class="flex items-center gap-2"><CheckCircle class="w-4 h-4 text-accent-green flex-shrink-0" /> Company profile required</li>
                    <li class="flex items-center gap-2"><CheckCircle class="w-4 h-4 text-accent-green flex-shrink-0" /> Earn override commissions from team</li>
                    <li class="flex items-center gap-2"><CheckCircle class="w-4 h-4 text-accent-green flex-shrink-0" /> Full network management tools</li>
                  </ul>
                </div>
              </label>
            </div>

            <div v-if="pkg.choice === 'business_partner'" class="p-4 rounded-lg bg-accent-orange/10 border border-accent-orange/30 flex items-start gap-3">
              <AlertTriangle class="w-5 h-5 text-accent-orange flex-shrink-0 mt-0.5" />
              <p class="text-sm text-stone-700">Business Partner package requires a <strong>Company profile</strong> in the next step.</p>
            </div>

            <div class="flex justify-between pt-6">
              <Button type="button" variant="outline" @click="prevStep">← Back</Button>
              <Button type="button" @click="nextStep" :disabled="!pkg.choice">Next →</Button>
            </div>
          </div>

          <!-- ── STEP 3: Profile + Credentials ── -->
          <div v-else-if="currentStep === 2" class="space-y-6">
            <div class="text-center mb-6">
              <h2 class="text-2xl font-bold text-forest-dark mb-2">Your Details</h2>
              <p class="text-stone-600">Fill in your profile information</p>
            </div>

            <!-- Profile type toggle -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <label
                class="relative cursor-pointer rounded-lg border-2 transition-all p-4"
                :class="[profile.type === 'individual' ? 'border-gold bg-gold/5' : 'border-stone-200', pkg.choice === 'business_partner' ? 'opacity-50 cursor-not-allowed' : 'hover:border-gold/50']"
              >
                <input v-model="profile.type" type="radio" value="individual" class="sr-only" :disabled="pkg.choice === 'business_partner'" />
                <div class="flex items-center gap-3">
                  <User class="w-5 h-5 text-forest-light" />
                  <div><div class="font-medium text-forest-dark">Individual</div><div class="text-xs text-stone-500">Personal registration</div></div>
                  <div class="ml-auto w-4 h-4 rounded-full border-2 flex items-center justify-center" :class="profile.type === 'individual' ? 'border-gold' : 'border-stone-300'">
                    <div v-if="profile.type === 'individual'" class="w-2 h-2 bg-gold rounded-full"></div>
                  </div>
                </div>
              </label>
              <label class="relative cursor-pointer rounded-lg border-2 transition-all p-4 hover:border-gold/50" :class="profile.type === 'company' ? 'border-gold bg-gold/5' : 'border-stone-200'">
                <input v-model="profile.type" type="radio" value="company" class="sr-only" />
                <div class="flex items-center gap-3">
                  <Building2 class="w-5 h-5 text-gold" />
                  <div><div class="font-medium text-forest-dark">Company</div><div class="text-xs text-stone-500">Registered business</div></div>
                  <div class="ml-auto w-4 h-4 rounded-full border-2 flex items-center justify-center" :class="profile.type === 'company' ? 'border-gold' : 'border-stone-300'">
                    <div v-if="profile.type === 'company'" class="w-2 h-2 bg-gold rounded-full"></div>
                  </div>
                </div>
              </label>
            </div>

            <!-- Individual fields -->
            <div v-if="profile.type === 'individual'" class="space-y-4">
              <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <FormField label="Full Name *" :error="profileErrors.individual_name">
                  <Input v-model="profile.individual_name" placeholder="As per IC" />
                </FormField>
                <FormField label="NRIC / Passport No. *" :error="profileErrors.individual_id_number">
                  <Input v-model="profile.individual_id_number" placeholder="e.g. 900101-01-1234" />
                </FormField>
                <FormField label="Phone Number *" :error="profileErrors.individual_phone">
                  <Input v-model="profile.individual_phone" placeholder="+60123456789" />
                </FormField>
                <FormField label="Email Address *" :error="profileErrors.login_email">
                  <Input v-model="profile.login_email" type="email" placeholder="your@email.com" />
                </FormField>
              </div>
              <FormField label="Home Address *" :error="profileErrors.individual_address">
                <textarea v-model="profile.individual_address" rows="3" class="w-full px-3 py-2 border border-stone-300 rounded-lg focus:ring-2 focus:ring-gold focus:border-gold text-sm" placeholder="Full address"></textarea>
              </FormField>
              <FormField label="IC / Passport Scan *" :error="profileErrors.individual_id_file">
                <input type="file" @change="e => profile.individual_id_file = e.target.files[0]" accept=".jpg,.jpeg,.png,.pdf" class="block w-full text-sm text-stone-600 file:mr-3 file:py-2 file:px-4 file:rounded-lg file:border-0 file:bg-forest-dark file:text-white file:text-sm hover:file:bg-forest-light" />
                <p class="text-xs text-stone-400 mt-1">JPG, PNG or PDF, max 5MB</p>
              </FormField>
            </div>

            <!-- Company fields -->
            <div v-if="profile.type === 'company'" class="space-y-4">
              <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <FormField label="Company Name *" :error="profileErrors.company_name">
                  <Input v-model="profile.company_name" placeholder="Registered company name" />
                </FormField>
                <FormField label="Company Reg. No. *" :error="profileErrors.company_registration_number">
                  <Input v-model="profile.company_registration_number" placeholder="SSM or equivalent" />
                </FormField>
                <FormField label="Company Phone *" :error="profileErrors.company_phone">
                  <Input v-model="profile.company_phone" placeholder="+60312345678" />
                </FormField>
                <FormField label="Company Email *" :error="profileErrors.company_email_address">
                  <Input v-model="profile.company_email_address" type="email" placeholder="company@example.com" />
                </FormField>
              </div>
              <FormField label="Company Address *" :error="profileErrors.company_address">
                <textarea v-model="profile.company_address" rows="3" class="w-full px-3 py-2 border border-stone-300 rounded-lg focus:ring-2 focus:ring-gold focus:border-gold text-sm" placeholder="Registered address"></textarea>
              </FormField>
              <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <FormField label="Representative Name *" :error="profileErrors.company_representative_name">
                  <Input v-model="profile.company_representative_name" placeholder="Person acting for company" />
                </FormField>
                <FormField label="Representative IC / Passport No. *" :error="profileErrors.company_representative_id_number">
                  <Input v-model="profile.company_representative_id_number" placeholder="IC / Passport number" />
                </FormField>
              </div>
              <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <FormField label="Company Registration Doc *" :error="profileErrors.company_reg_file">
                  <input type="file" @change="e => profile.company_reg_file = e.target.files[0]" accept=".pdf,.jpg,.jpeg,.png" class="block w-full text-sm text-stone-600 file:mr-3 file:py-2 file:px-4 file:rounded-lg file:border-0 file:bg-forest-dark file:text-white file:text-sm hover:file:bg-forest-light" />
                  <p class="text-xs text-stone-400 mt-1">PDF/JPG/PNG, max 10MB</p>
                </FormField>
                <FormField label="Representative IC Scan *" :error="profileErrors.company_representative_id_file">
                  <input type="file" @change="e => profile.company_representative_id_file = e.target.files[0]" accept=".jpg,.jpeg,.png,.pdf" class="block w-full text-sm text-stone-600 file:mr-3 file:py-2 file:px-4 file:rounded-lg file:border-0 file:bg-forest-dark file:text-white file:text-sm hover:file:bg-forest-light" />
                  <p class="text-xs text-stone-400 mt-1">JPG/PNG/PDF, max 5MB</p>
                </FormField>
              </div>
              <FormField label="Login Email *" :error="profileErrors.login_email">
                <Input v-model="profile.login_email" type="email" placeholder="Email used to log in (may differ from company email)" />
              </FormField>
            </div>

            <!-- Bank Account -->
            <div class="pt-2">
              <h3 class="text-sm font-semibold text-forest-dark mb-3 flex items-center gap-2"><CreditCard class="w-4 h-4 text-gold" /> Bank Account (for payouts)</h3>
              <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <FormField label="Bank Name *" :error="profileErrors.bank_name">
                  <Input v-model="profile.bank_name" placeholder="e.g. Maybank" />
                </FormField>
                <FormField label="Account Name *" :error="profileErrors.bank_account_name">
                  <Input v-model="profile.bank_account_name" placeholder="Account holder name" />
                </FormField>
                <FormField label="Account Number *" :error="profileErrors.bank_account_number">
                  <Input v-model="profile.bank_account_number" placeholder="Account number" />
                </FormField>
              </div>
            </div>

            <!-- Email pre-check warning -->
            <div v-if="emailCheckResult === 'exists'" class="p-4 rounded-lg bg-accent-red/10 border border-accent-red flex items-start gap-3">
              <AlertCircle class="w-5 h-5 text-accent-red flex-shrink-0 mt-0.5" />
              <div>
                <p class="text-sm font-medium text-accent-red">This email is already registered.</p>
                <a href="/login" class="text-sm text-accent-blue underline">Log in to your account →</a>
              </div>
            </div>
            <div v-if="emailCheckResult === 'needs_reset'" class="p-4 rounded-lg bg-yellow-50 border border-yellow-300 flex items-start gap-3">
              <AlertTriangle class="w-5 h-5 text-yellow-600 flex-shrink-0 mt-0.5" />
              <div>
                <p class="text-sm font-medium text-yellow-800">This email requires a password reset first.</p>
                <a href="/forgot-password" class="text-sm text-accent-blue underline">Reset password →</a>
              </div>
            </div>

            <!-- Login Credentials -->
            <div class="pt-2 border-t border-stone-200">
              <h3 class="text-sm font-semibold text-forest-dark mb-3 flex items-center gap-2"><Lock class="w-4 h-4 text-gold" /> Login Credentials</h3>
              <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <FormField label="Password *" :error="profileErrors.password">
                  <div class="relative">
                    <Input v-model="profile.password" :type="showPassword ? 'text' : 'password'" placeholder="Min 8 characters" class="pr-10" />
                    <button type="button" @click="showPassword = !showPassword" class="absolute right-3 top-1/2 -translate-y-1/2 text-stone-400 hover:text-stone-600">
                      <Eye v-if="!showPassword" class="w-4 h-4" />
                      <EyeOff v-else class="w-4 h-4" />
                    </button>
                  </div>
                </FormField>
                <FormField label="Confirm Password *" :error="profileErrors.password_confirmation">
                  <div class="relative">
                    <Input v-model="profile.password_confirmation" :type="showPassword ? 'text' : 'password'" placeholder="Re-enter password" class="pr-10" />
                  </div>
                </FormField>
              </div>
            </div>

            <div class="flex justify-between pt-6">
              <Button type="button" variant="outline" @click="prevStep">← Back</Button>
              <Button type="button" @click="proceedFromStep3" :disabled="step3Submitting">
                <span v-if="step3Submitting" class="flex items-center gap-2"><Loader2 class="w-4 h-4 animate-spin" /> Sending code…</span>
                <span v-else>Next →</span>
              </Button>
            </div>
          </div>

          <!-- ── STEP 4: Email Verification ── -->
          <div v-else-if="currentStep === 3" class="space-y-6">
            <div class="text-center mb-6">
              <h2 class="text-2xl font-bold text-forest-dark mb-2">Verify Your Email</h2>
              <p class="text-stone-600">We've sent a 6-digit code to <strong>{{ profile.login_email }}</strong></p>
            </div>

            <!-- 6-digit input -->
            <div class="flex justify-center gap-3">
              <input
                v-for="(_, i) in 6"
                :key="i"
                :ref="el => codeInputs[i] = el"
                v-model="codeDigits[i]"
                @input="onCodeInput(i)"
                @keydown="onCodeKeydown(i, $event)"
                @paste="onCodePaste($event)"
                type="text"
                maxlength="1"
                inputmode="numeric"
                pattern="[0-9]"
                class="w-12 h-14 text-center text-xl font-bold border-2 rounded-lg focus:ring-2 focus:ring-gold focus:border-gold transition-colors"
                :class="verifyError ? 'border-accent-red' : 'border-stone-300'"
              />
            </div>

            <div v-if="verifyError" class="text-center text-sm text-accent-red font-medium">{{ verifyError }}</div>

            <!-- Countdown -->
            <div class="text-center">
              <p v-if="codeExpiresIn > 0" class="text-sm text-stone-500">
                Code expires in: <span class="font-mono font-semibold text-forest-dark">{{ formatCountdown(codeExpiresIn) }}</span>
              </p>
              <p v-else class="text-sm text-accent-red font-medium">Code has expired. Please resend.</p>
            </div>

            <!-- Resend -->
            <div class="text-center">
              <button
                type="button"
                @click="resendCode"
                :disabled="resendCooldown > 0 || resendLoading"
                class="text-sm text-accent-blue underline disabled:text-stone-400 disabled:no-underline"
              >
                <span v-if="resendCooldown > 0">Resend available in {{ resendCooldown }}s</span>
                <span v-else-if="resendLoading">Resending…</span>
                <span v-else>Resend Code</span>
              </button>
            </div>

            <div class="flex justify-between pt-6">
              <Button type="button" variant="outline" @click="prevStep">← Back</Button>
              <Button type="button" @click="verifyCode" :disabled="fullCode.length < 6 || verifying">
                <span v-if="verifying" class="flex items-center gap-2"><Loader2 class="w-4 h-4 animate-spin" /> Verifying…</span>
                <span v-else>Verify & Continue →</span>
              </Button>
            </div>
          </div>

          <!-- ── STEP 5: T&C + Payment ── -->
          <div v-else-if="currentStep === 4" class="space-y-6">
            <div class="text-center mb-6">
              <h2 class="text-2xl font-bold text-forest-dark mb-2">Terms & Payment</h2>
              <p class="text-stone-600">Almost there! Accept the terms and choose your payment method.</p>
            </div>

            <!-- Package summary -->
            <div class="bg-cream rounded-lg p-4 border border-stone-200">
              <div class="flex justify-between items-center">
                <div>
                  <p class="text-sm text-stone-500">Registration Package</p>
                  <p class="font-semibold text-forest-dark">{{ pkg.choice === 'business_partner' ? 'Business Partner' : 'Agent / Agent Leader' }}</p>
                </div>
                <div class="text-right">
                  <p class="text-sm text-stone-500">Entry Fee</p>
                  <p class="text-2xl font-bold text-gold">{{ formatCurrency('RM', pkg.choice === 'business_partner' ? entryFeeBusinessPartner : entryFeeAgent) }}</p>
                </div>
              </div>
            </div>

            <!-- T&C -->
            <label class="flex items-start gap-3 cursor-pointer">
              <input v-model="payment.tcAccepted" type="checkbox" class="mt-1 w-4 h-4 text-gold border-stone-300 rounded" />
              <span class="text-sm text-stone-700">I have read and agree to the <a href="/terms" target="_blank" class="text-accent-blue underline">Terms & Conditions ↗</a></span>
            </label>

            <!-- Payment method -->
            <div class="space-y-4" :class="!payment.tcAccepted ? 'opacity-50 pointer-events-none' : ''">
              <h3 class="text-sm font-semibold text-forest-dark">How would you like to pay?</h3>

              <!-- Stripe -->
              <label class="cursor-pointer">
                <div class="p-4 border-2 rounded-lg transition-all" :class="payment.method === 'stripe' ? 'border-gold bg-gold/5' : 'border-stone-200 hover:border-gold/30'">
                  <div class="flex items-center gap-3">
                    <div class="w-5 h-5 rounded-full border-2 flex items-center justify-center flex-shrink-0" :class="payment.method === 'stripe' ? 'border-gold' : 'border-stone-300'">
                      <div v-if="payment.method === 'stripe'" class="w-3 h-3 bg-gold rounded-full"></div>
                    </div>
                    <input v-model="payment.method" type="radio" value="stripe" class="sr-only" />
                    <div>
                      <p class="font-medium text-forest-dark">Pay via Card (Stripe)</p>
                      <p class="text-xs text-stone-500">Secure payment via Stripe Checkout</p>
                    </div>
                    <CreditCard class="w-5 h-5 text-gold ml-auto" />
                  </div>
                </div>
              </label>

              <!-- Manual transfer -->
              <label class="cursor-pointer">
                <div class="p-4 border-2 rounded-lg transition-all" :class="payment.method === 'manual' ? 'border-gold bg-gold/5' : 'border-stone-200 hover:border-gold/30'">
                  <div class="flex items-center gap-3">
                    <div class="w-5 h-5 rounded-full border-2 flex items-center justify-center flex-shrink-0" :class="payment.method === 'manual' ? 'border-gold' : 'border-stone-300'">
                      <div v-if="payment.method === 'manual'" class="w-3 h-3 bg-gold rounded-full"></div>
                    </div>
                    <input v-model="payment.method" type="radio" value="manual" class="sr-only" />
                    <div>
                      <p class="font-medium text-forest-dark">Manual Bank Transfer</p>
                      <p class="text-xs text-stone-500">Upload receipt after transferring</p>
                    </div>
                    <Banknote class="w-5 h-5 text-forest-light ml-auto" />
                  </div>
                </div>
              </label>

              <!-- Manual bank details + receipt -->
              <div v-if="payment.method === 'manual'" class="ml-6 space-y-4">
                <div v-if="companyBank" class="p-4 bg-stone-50 rounded-lg border border-stone-200 text-sm space-y-1">
                  <p class="font-semibold text-forest-dark mb-2">Bank Transfer Details</p>
                  <p><span class="text-stone-500">Bank:</span> <span class="font-medium">{{ companyBank.bank_name }}</span></p>
                  <p><span class="text-stone-500">Account Name:</span> <span class="font-medium">{{ companyBank.account_name }}</span></p>
                  <p><span class="text-stone-500">Account No:</span> <span class="font-mono font-medium">{{ companyBank.account_number }}</span></p>
                </div>
                <FormField label="Upload Receipt *" :error="paymentErrors.receipt_file">
                  <input type="file" @change="e => payment.receiptFile = e.target.files[0]" accept=".pdf,.jpg,.jpeg,.png" class="block w-full text-sm text-stone-600 file:mr-3 file:py-2 file:px-4 file:rounded-lg file:border-0 file:bg-forest-dark file:text-white file:text-sm hover:file:bg-forest-light" />
                  <p class="text-xs text-stone-400 mt-1">PDF/JPG/PNG, max 5MB</p>
                </FormField>
                <FormField label="Reference / Note (optional)">
                  <Input v-model="payment.reference" placeholder="e.g. transfer reference number" />
                </FormField>
              </div>
            </div>

            <!-- Action buttons -->
            <div v-if="payment.tcAccepted" class="space-y-3 pt-4">
              <div v-if="payment.method === 'stripe'">
                <Button type="button" class="w-full" @click="payWithStripe" :disabled="paymentSubmitting">
                  <span v-if="paymentSubmitting" class="flex items-center justify-center gap-2"><Loader2 class="w-4 h-4 animate-spin" /> Redirecting…</span>
                  <span v-else>Pay with Stripe →</span>
                </Button>
              </div>
              <div v-else-if="payment.method === 'manual'">
                <Button type="button" class="w-full" @click="submitManualPayment" :disabled="!payment.receiptFile || paymentSubmitting">
                  <span v-if="paymentSubmitting" class="flex items-center justify-center gap-2"><Loader2 class="w-4 h-4 animate-spin" /> Submitting…</span>
                  <span v-else>Complete Registration →</span>
                </Button>
              </div>
            </div>

            <div class="relative my-4">
              <div class="absolute inset-0 flex items-center"><div class="w-full border-t border-stone-200"></div></div>
              <div class="relative flex justify-center text-xs text-stone-400 bg-white px-3">or</div>
            </div>

            <Button
              type="button"
              variant="outline"
              class="w-full"
              @click="skipPayment"
              :disabled="!payment.tcAccepted || paymentSubmitting"
            >
              Skip Payment for Now →
            </Button>
            <p class="text-xs text-center text-stone-400">You're already registered. Log in and complete payment from your dashboard.</p>

            <div class="flex justify-start pt-2">
              <Button type="button" variant="ghost" @click="prevStep" size="sm">← Back</Button>
            </div>
          </div>

          <!-- ── STEP 6: Confirmation ── -->
          <div v-else-if="currentStep === 5" class="space-y-6">
            <div class="text-center py-8">
              <div class="w-20 h-20 bg-accent-green/10 rounded-full flex items-center justify-center mx-auto mb-6">
                <CheckCircle class="w-10 h-10 text-accent-green" />
              </div>
              <h2 class="text-2xl font-bold text-forest-dark mb-2">Registration Submitted!</h2>
              <p class="text-stone-600">Your application is under review.</p>
            </div>

            <div class="bg-cream rounded-xl p-6 border border-stone-200">
              <h3 class="font-semibold text-forest-dark mb-4">What happens next:</h3>
              <ol class="space-y-3">
                <li class="flex items-start gap-3">
                  <span class="w-6 h-6 rounded-full bg-forest-light/20 text-forest-dark text-xs font-bold flex items-center justify-center flex-shrink-0 mt-0.5">1</span>
                  <span class="text-sm text-stone-700">Our team will verify your documents.</span>
                </li>
                <li class="flex items-start gap-3">
                  <span class="w-6 h-6 rounded-full bg-forest-light/20 text-forest-dark text-xs font-bold flex items-center justify-center flex-shrink-0 mt-0.5">2</span>
                  <span class="text-sm text-stone-700">If you paid by bank transfer, we will verify your receipt.</span>
                </li>
                <li class="flex items-start gap-3">
                  <span class="w-6 h-6 rounded-full bg-forest-light/20 text-forest-dark text-xs font-bold flex items-center justify-center flex-shrink-0 mt-0.5">3</span>
                  <span class="text-sm text-stone-700">You will receive an email once approved.</span>
                </li>
              </ol>
              <p class="text-sm text-stone-600 mt-4">You can log in at any time to check your status.</p>
            </div>

            <div class="flex flex-col sm:flex-row gap-3 pt-4">
              <a href="/login" class="flex-1">
                <Button class="w-full">Log In to My Account</Button>
              </a>
              <a href="/" class="flex-1">
                <Button variant="outline" class="w-full">Back to Home</Button>
              </a>
            </div>
          </div>
        </Card>
      </div>
    </div>

    <!-- Invalid Email Dialog -->
    <DialogModal :show="showInvalidEmailDialog" @close="handleDialogClose" :closeable="false">
      <template #title>Invalid Email Address</template>
      <template #content>
        <p>The email address provided is not in a valid format. Please use a valid email address to register as an agent.</p>
      </template>
      <template #footer>
        <Button @click="handleDialogClose">OK</Button>
      </template>
    </DialogModal>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { router } from '@inertiajs/vue3'
import {
  ArrowLeft, CheckCircle, AlertCircle, AlertTriangle,
  Users, Building2, User, CreditCard, Lock, Eye, EyeOff,
  Loader2, Banknote
} from 'lucide-vue-next'
import Card from './Design/Components/Card.vue'
import Button from './Design/Components/Button.vue'
import Input from './Design/Components/Input.vue'
import FormField from './Design/Components/FormField.vue'
import DialogModal from '@/Components/DialogModal.vue'
import { formatCurrency } from '../lib/utils.js'

const props = defineProps({
  email: { type: String, default: '' },
  invalidEmail: { type: Boolean, default: false },
  errors: { type: Object, default: () => ({}) },
  entryFeeAgent: { type: [Number, String], default: 100 },
  entryFeeBusinessPartner: { type: [Number, String], default: 3000 },
  companyBank: { type: Object, default: null },
})

const currentStep = ref(0)
const showInvalidEmailDialog = ref(false)

const steps = [
  { label: 'Referral ID', key: 'referral' },
  { label: 'Package', key: 'package' },
  { label: 'Your Details', key: 'details' },
  { label: 'Email Verify', key: 'verify' },
  { label: 'T&C + Payment', key: 'payment' },
  { label: 'Done', key: 'done' },
]

// ── Step 1 ──
const referral = ref({ choice: '', code: '', validating: false, status: '', agentName: '', errorMessage: '' })

const resetReferralValidation = () => { referral.value.status = ''; referral.value.agentName = ''; referral.value.errorMessage = '' }

const validateReferralCode = async () => {
  const code = referral.value.code.trim()
  if (!code) return
  referral.value.validating = true
  resetReferralValidation()
  try {
    const res = await fetch(`/api/agents/track/code/${encodeURIComponent(code)}`, { headers: { Accept: 'application/json' } })
    const json = await res.json().catch(() => ({}))
    if (res.ok && json.success && json.data) {
      referral.value.status = 'valid'
      referral.value.agentName = json.data.agent_name || 'Unknown agent'
    } else {
      referral.value.status = 'invalid'
      referral.value.errorMessage = json.message || 'Referral ID is invalid or expired.'
    }
  } catch {
    referral.value.status = 'invalid'
    referral.value.errorMessage = 'Unable to validate. Please try again.'
  } finally {
    referral.value.validating = false
  }
}

const canProceedFromStep1 = computed(() => {
  if (referral.value.choice === 'no') return true
  return referral.value.choice === 'yes' && referral.value.status === 'valid'
})

// ── Step 2 ──
const pkg = ref({ choice: '' })

// ── Step 3 ──
const profile = ref({
  type: 'individual',
  individual_name: '',
  individual_id_number: '',
  individual_phone: '',
  individual_address: '',
  individual_id_file: null,
  company_name: '',
  company_registration_number: '',
  company_address: '',
  company_phone: '',
  company_email_address: '',
  company_representative_name: '',
  company_representative_id_number: '',
  company_reg_file: null,
  company_representative_id_file: null,
  login_email: props.email || '',
  password: '',
  password_confirmation: '',
  bank_name: '',
  bank_account_name: '',
  bank_account_number: '',
})
const profileErrors = ref({})
const emailCheckResult = ref('')
const showPassword = ref(false)
const step3Submitting = ref(false)

const proceedFromStep3 = async () => {
  profileErrors.value = {}
  emailCheckResult.value = ''

  // Client-side validation
  const errs = {}
  const loginEmail = profile.value.login_email?.trim()
  if (!loginEmail) errs.login_email = 'Email is required'
  if (!profile.value.password) errs.password = 'Password is required'
  if (profile.value.password.length < 8) errs.password = 'Password must be at least 8 characters'
  if (profile.value.password !== profile.value.password_confirmation) errs.password_confirmation = 'Passwords do not match'

  if (profile.value.type === 'individual') {
    if (!profile.value.individual_name) errs.individual_name = 'Full name is required'
    if (!profile.value.individual_id_number) errs.individual_id_number = 'NRIC / Passport is required'
    if (!profile.value.individual_phone) errs.individual_phone = 'Phone is required'
    if (!profile.value.individual_address) errs.individual_address = 'Address is required'
    if (!profile.value.individual_id_file) errs.individual_id_file = 'IC scan is required'
  } else {
    if (!profile.value.company_name) errs.company_name = 'Company name is required'
    if (!profile.value.company_registration_number) errs.company_registration_number = 'Reg. number is required'
    if (!profile.value.company_phone) errs.company_phone = 'Phone is required'
    if (!profile.value.company_email_address) errs.company_email_address = 'Company email is required'
    if (!profile.value.company_address) errs.company_address = 'Address is required'
    if (!profile.value.company_representative_name) errs.company_representative_name = 'Rep. name is required'
    if (!profile.value.company_representative_id_number) errs.company_representative_id_number = 'Rep. IC is required'
    if (!profile.value.company_reg_file) errs.company_reg_file = 'Registration document is required'
    if (!profile.value.company_representative_id_file) errs.company_representative_id_file = 'Rep. IC scan is required'
  }
  if (!profile.value.bank_name) errs.bank_name = 'Bank name is required'
  if (!profile.value.bank_account_name) errs.bank_account_name = 'Account name is required'
  if (!profile.value.bank_account_number) errs.bank_account_number = 'Account number is required'

  if (Object.keys(errs).length) { profileErrors.value = errs; return }

  step3Submitting.value = true
  const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
  try {
    // Pre-check email
    const checkRes = await fetch('/get-started/check-email', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
      body: JSON.stringify({ email: loginEmail }),
    })
    const checkJson = await checkRes.json().catch(() => ({}))
    if (checkJson.status === 'login') { emailCheckResult.value = 'exists'; step3Submitting.value = false; return }
    if (checkJson.status === 'reset' || checkJson.status === 'no_password') { emailCheckResult.value = 'needs_reset'; step3Submitting.value = false; return }

    // Save profile draft to backend session (includes files)
    const draftData = new FormData()
    draftData.append('email', loginEmail)
    draftData.append('profile_type', profile.value.type)
    draftData.append('package', pkg.value.choice)
    draftData.append('password', profile.value.password)
    draftData.append('password_confirmation', profile.value.password_confirmation)
    draftData.append('bank_name', profile.value.bank_name)
    draftData.append('bank_account_name', profile.value.bank_account_name)
    draftData.append('bank_account_number', profile.value.bank_account_number)
    if (referral.value.choice === 'yes' && referral.value.status === 'valid') {
      draftData.append('referral_code', referral.value.code)
    }
    if (profile.value.type === 'individual') {
      draftData.append('individual_name', profile.value.individual_name)
      draftData.append('individual_id_number', profile.value.individual_id_number)
      draftData.append('individual_phone', profile.value.individual_phone)
      draftData.append('individual_address', profile.value.individual_address)
      if (profile.value.individual_id_file) draftData.append('individual_id_file', profile.value.individual_id_file)
    } else {
      draftData.append('company_name', profile.value.company_name)
      draftData.append('company_registration_number', profile.value.company_registration_number)
      draftData.append('company_phone', profile.value.company_phone)
      draftData.append('company_email_address', profile.value.company_email_address)
      draftData.append('company_address', profile.value.company_address)
      draftData.append('company_representative_name', profile.value.company_representative_name)
      draftData.append('company_representative_id_number', profile.value.company_representative_id_number)
      if (profile.value.company_reg_file) draftData.append('company_reg_file', profile.value.company_reg_file)
      if (profile.value.company_representative_id_file) draftData.append('company_representative_id_file', profile.value.company_representative_id_file)
    }

    const draftRes = await fetch('/register-as-agent/save-draft', {
      method: 'POST',
      headers: { Accept: 'application/json', 'X-CSRF-TOKEN': csrfToken, 'X-Requested-With': 'XMLHttpRequest' },
      body: draftData,
    })
    if (!draftRes.ok) {
      const draftErr = await draftRes.json().catch(() => ({}))
      profileErrors.value.login_email = draftErr.message || 'Failed to save registration data. Please try again.'
      step3Submitting.value = false
      return
    }

    // Send verification code
    const formData = new FormData()
    formData.append('email', loginEmail)
    const codeRes = await fetch('/register-as-agent/resend-code', {
      method: 'POST',
      headers: { Accept: 'application/json', 'X-CSRF-TOKEN': csrfToken, 'X-Requested-With': 'XMLHttpRequest' },
      body: formData,
    })
    if (codeRes.ok || codeRes.status === 302) {
      startExpiryTimer()
      startResendCooldown()
      currentStep.value = 3
    } else {
      const errJson = await codeRes.json().catch(() => ({}))
      profileErrors.value.login_email = errJson.errors?.email?.[0] || 'Failed to send verification code. Please try again.'
    }
  } catch {
    profileErrors.value.login_email = 'Unable to proceed. Please check your connection.'
  } finally {
    step3Submitting.value = false
  }
}

// ── Step 4 ──
const codeDigits = ref(['', '', '', '', '', ''])
const codeInputs = ref([])
const verifyError = ref('')
const verifying = ref(false)
const codeExpiresIn = ref(15 * 60) // 15 minutes
const resendCooldown = ref(60)
const resendLoading = ref(false)
let expiryTimer = null
let resendTimer = null
let cooldownTimer = null

const fullCode = computed(() => codeDigits.value.join(''))

function startExpiryTimer() {
  codeExpiresIn.value = 15 * 60
  clearInterval(expiryTimer)
  expiryTimer = setInterval(() => {
    if (codeExpiresIn.value > 0) codeExpiresIn.value--
    else clearInterval(expiryTimer)
  }, 1000)
}

function startResendCooldown() {
  resendCooldown.value = 60
  clearInterval(cooldownTimer)
  cooldownTimer = setInterval(() => {
    if (resendCooldown.value > 0) resendCooldown.value--
    else clearInterval(cooldownTimer)
  }, 1000)
}

function formatCountdown(secs) {
  const m = Math.floor(secs / 60).toString().padStart(2, '0')
  const s = (secs % 60).toString().padStart(2, '0')
  return `${m}:${s}`
}

function onCodeInput(index) {
  const val = codeDigits.value[index].replace(/\D/g, '')
  codeDigits.value[index] = val.slice(-1)
  verifyError.value = ''
  if (val && index < 5) codeInputs.value[index + 1]?.focus()
}

function onCodeKeydown(index, e) {
  if (e.key === 'Backspace' && !codeDigits.value[index] && index > 0) {
    codeInputs.value[index - 1]?.focus()
  }
}

function onCodePaste(e) {
  e.preventDefault()
  const pasted = e.clipboardData.getData('text').replace(/\D/g, '').slice(0, 6)
  for (let i = 0; i < 6; i++) codeDigits.value[i] = pasted[i] || ''
  codeInputs.value[Math.min(pasted.length, 5)]?.focus()
}

const verifyCode = async () => {
  if (fullCode.value.length < 6) return
  verifying.value = true
  verifyError.value = ''
  try {
    const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
    const formData = new FormData()
    formData.append('email', profile.value.login_email)
    formData.append('code', fullCode.value)
    const res = await fetch('/register-as-agent/verify-email', {
      method: 'POST',
      headers: { Accept: 'application/json', 'X-CSRF-TOKEN': csrf, 'X-Requested-With': 'XMLHttpRequest' },
      body: formData,
    })
    if (res.ok || res.status === 302) {
      clearInterval(expiryTimer)
      clearInterval(cooldownTimer)
      currentStep.value = 4
    } else {
      const json = await res.json().catch(() => ({}))
      verifyError.value = json.errors?.code?.[0] || json.message || 'Invalid or expired code. Please try again.'
    }
  } catch {
    verifyError.value = 'Unable to verify. Please check your connection.'
  } finally {
    verifying.value = false
  }
}

const resendCode = async () => {
  if (resendCooldown.value > 0 || resendLoading.value) return
  resendLoading.value = true
  try {
    const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
    const formData = new FormData()
    formData.append('email', profile.value.login_email)
    const res = await fetch('/register-as-agent/resend-code', {
      method: 'POST',
      headers: { Accept: 'application/json', 'X-CSRF-TOKEN': csrf, 'X-Requested-With': 'XMLHttpRequest' },
      body: formData,
    })
    if (res.ok || res.status === 302) {
      codeDigits.value = ['', '', '', '', '', '']
      verifyError.value = ''
      startExpiryTimer()
      startResendCooldown()
    } else {
      const json = await res.json().catch(() => ({}))
      verifyError.value = json.errors?.email?.[0] || 'Failed to resend. Please try again.'
    }
  } catch {
    verifyError.value = 'Unable to resend. Please check your connection.'
  } finally {
    resendLoading.value = false
  }
}

// ── Step 5 ──
const payment = ref({ tcAccepted: false, method: '', receiptFile: null, reference: '' })
const paymentErrors = ref({})
const paymentSubmitting = ref(false)

const payWithStripe = async () => {
  paymentSubmitting.value = true
  try {
    const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
    const body = new FormData()
    body.append('email', profile.value.login_email)
    body.append('package', pkg.value.choice)
    const res = await fetch('/register-as-agent/initiate-stripe', {
      method: 'POST',
      headers: { Accept: 'application/json', 'X-CSRF-TOKEN': csrf, 'X-Requested-With': 'XMLHttpRequest' },
      body,
    })
    const json = await res.json().catch(() => ({}))
    if (json.url) {
      window.location.href = json.url
    } else {
      // Stripe not configured: show confirmation screen
      currentStep.value = 5
    }
  } catch {
    // Fallback to confirmation
    currentStep.value = 5
  } finally {
    paymentSubmitting.value = false
  }
}

const submitManualPayment = async () => {
  paymentErrors.value = {}
  if (!payment.value.receiptFile) { paymentErrors.value.receipt_file = 'Please upload your transfer receipt'; return }
  paymentSubmitting.value = true
  try {
    const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
    const body = new FormData()
    body.append('email', profile.value.login_email)
    body.append('package', pkg.value.choice)
    body.append('receipt_file', payment.value.receiptFile)
    if (payment.value.reference) body.append('reference', payment.value.reference)
    const res = await fetch('/register-as-agent/submit-payment', {
      method: 'POST',
      headers: { Accept: 'application/json', 'X-CSRF-TOKEN': csrf, 'X-Requested-With': 'XMLHttpRequest' },
      body,
    })
    if (res.ok || res.status === 302) {
      currentStep.value = 5
    } else {
      const json = await res.json().catch(() => ({}))
      paymentErrors.value.receipt_file = json.message || 'Failed to submit. Please try again.'
    }
  } catch {
    paymentErrors.value.receipt_file = 'Unable to submit. Please check your connection.'
  } finally {
    paymentSubmitting.value = false
  }
}

const skipPayment = async () => {
  paymentSubmitting.value = true
  try {
    const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
    const body = new FormData()
    body.append('email', profile.value.login_email)
    const res = await fetch('/register-as-agent/skip-payment', {
      method: 'POST',
      headers: { Accept: 'application/json', 'X-CSRF-TOKEN': csrf, 'X-Requested-With': 'XMLHttpRequest' },
      body,
    })
    if (res.ok || res.status === 302) {
      // Auto-logged in — redirect to dashboard
      const json = await res.json().catch(() => ({}))
      window.location.href = json.redirect || '/agent/dashboard'
    } else {
      currentStep.value = 5
    }
  } catch {
    currentStep.value = 5
  } finally {
    paymentSubmitting.value = false
  }
}

// ── Navigation ──
const nextStep = () => {
  if (currentStep.value === 0 && !canProceedFromStep1.value) return
  if (currentStep.value === 1 && !pkg.value.choice) return
  if (currentStep.value < steps.length - 1) currentStep.value++
}

const prevStep = () => {
  if (currentStep.value > 0) currentStep.value--
}

// ── Step helpers ──
const getStepClasses = (index) => {
  if (index < currentStep.value) return 'border-gold bg-gold text-white'
  if (index === currentStep.value) return 'border-gold bg-white text-gold'
  return 'border-stone-300 bg-white text-stone-400'
}
const getStepLabelClasses = (index) => {
  if (index < currentStep.value) return 'text-gold'
  if (index === currentStep.value) return 'text-forest-dark font-semibold'
  return 'text-stone-400'
}

// BP forces company profile
import { watch } from 'vue'
watch(() => pkg.value.choice, (val) => {
  if (val === 'business_partner') profile.value.type = 'company'
})

const handleDialogClose = () => { router.visit('/') }

onMounted(() => {
  if (props.invalidEmail) showInvalidEmailDialog.value = true
  if (props.email) profile.value.login_email = props.email
})

onUnmounted(() => {
  clearInterval(expiryTimer)
  clearInterval(cooldownTimer)
})
</script>
