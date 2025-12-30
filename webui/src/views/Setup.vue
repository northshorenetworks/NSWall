<template>
  <div class="setup-wizard">
    <div class="setup-container">
      <div class="setup-header">
        <h1>NSWall Setup</h1>
        <p class="subtitle">Initial Configuration Wizard</p>
      </div>

      <!-- Progress Steps -->
      <div class="progress-steps">
        <div
          v-for="(step, index) in steps"
          :key="index"
          :class="['step', {
            active: currentStep === index + 1,
            completed: currentStep > index + 1
          }]"
        >
          <div class="step-number">
            <span v-if="currentStep > index + 1">✓</span>
            <span v-else>{{ index + 1 }}</span>
          </div>
          <div class="step-label">{{ step.title }}</div>
        </div>
      </div>

      <!-- Step Content -->
      <div class="step-content">
        <!-- Step 1: Management Interface -->
        <div v-if="currentStep === 1" class="step-panel">
          <h2>Management Interface</h2>
          <p class="step-description">
            Select the network interface for management access. This is the interface
            you'll use to access the NSWall web interface.
          </p>

          <div class="form-group">
            <label>Network Interface</label>
            <select v-model="config.management_interface" @change="onInterfaceSelect">
              <option value="">-- Select Interface --</option>
              <option v-for="iface in interfaces" :key="iface.name" :value="iface.name">
                {{ iface.name }} - {{ iface.description || iface.mac }}
                <span v-if="iface.ipv4?.length">({{ iface.ipv4[0] }})</span>
              </option>
            </select>
          </div>

          <div v-if="selectedInterface" class="interface-details">
            <div class="detail-row">
              <span class="label">MAC Address:</span>
              <span>{{ selectedInterface.mac }}</span>
            </div>
            <div class="detail-row">
              <span class="label">Status:</span>
              <span :class="['status', selectedInterface.status]">{{ selectedInterface.status }}</span>
            </div>
            <div v-if="selectedInterface.media_type" class="detail-row">
              <span class="label">Media:</span>
              <span>{{ selectedInterface.media_type }}</span>
            </div>
          </div>

          <div class="form-group">
            <label class="checkbox-label">
              <input type="checkbox" v-model="config.use_dhcp">
              Use DHCP for IP configuration
            </label>
          </div>

          <div v-if="!config.use_dhcp" class="static-ip-config">
            <div class="form-group">
              <label>IP Address</label>
              <input
                type="text"
                v-model="config.management_ip"
                placeholder="192.168.1.1"
                :class="{ error: errors.management_ip }"
              >
              <span v-if="errors.management_ip" class="error-text">{{ errors.management_ip }}</span>
            </div>

            <div class="form-group">
              <label>Netmask</label>
              <select v-model="config.management_netmask">
                <option value="255.255.255.0">/24 (255.255.255.0)</option>
                <option value="255.255.255.128">/25 (255.255.255.128)</option>
                <option value="255.255.255.192">/26 (255.255.255.192)</option>
                <option value="255.255.254.0">/23 (255.255.254.0)</option>
                <option value="255.255.252.0">/22 (255.255.252.0)</option>
                <option value="255.255.0.0">/16 (255.255.0.0)</option>
              </select>
            </div>

            <div class="form-group">
              <label>Gateway (optional)</label>
              <input
                type="text"
                v-model="config.management_gateway"
                placeholder="192.168.1.254"
              >
            </div>
          </div>
        </div>

        <!-- Step 2: Admin Account -->
        <div v-if="currentStep === 2" class="step-panel">
          <h2>Administrator Account</h2>
          <p class="step-description">
            Create the administrator account for managing your NSWall firewall.
            Choose a strong password that you'll remember.
          </p>

          <div class="form-group">
            <label>Username</label>
            <input
              type="text"
              v-model="config.admin_username"
              placeholder="admin"
              :class="{ error: errors.admin_username }"
              autocomplete="username"
            >
            <span v-if="errors.admin_username" class="error-text">{{ errors.admin_username }}</span>
          </div>

          <div class="form-group">
            <label>Password</label>
            <div class="password-input">
              <input
                :type="showPassword ? 'text' : 'password'"
                v-model="config.admin_password"
                placeholder="Enter password"
                :class="{ error: errors.admin_password }"
                autocomplete="new-password"
              >
              <button type="button" class="toggle-password" @click="showPassword = !showPassword">
                {{ showPassword ? 'Hide' : 'Show' }}
              </button>
            </div>
            <span v-if="errors.admin_password" class="error-text">{{ errors.admin_password }}</span>
            <div class="password-strength" v-if="config.admin_password">
              <div class="strength-bar" :class="passwordStrength.class" :style="{ width: passwordStrength.width }"></div>
              <span class="strength-label">{{ passwordStrength.label }}</span>
            </div>
          </div>

          <div class="form-group">
            <label>Confirm Password</label>
            <input
              type="password"
              v-model="confirmPassword"
              placeholder="Confirm password"
              :class="{ error: confirmPassword && confirmPassword !== config.admin_password }"
              autocomplete="new-password"
            >
            <span v-if="confirmPassword && confirmPassword !== config.admin_password" class="error-text">
              Passwords do not match
            </span>
          </div>

          <div class="form-group">
            <label>Email (optional)</label>
            <input
              type="email"
              v-model="config.admin_email"
              placeholder="admin@example.com"
            >
          </div>

          <div class="password-requirements">
            <p>Password must contain:</p>
            <ul>
              <li :class="{ met: hasMinLength }">At least 8 characters</li>
              <li :class="{ met: hasUppercase }">One uppercase letter</li>
              <li :class="{ met: hasLowercase }">One lowercase letter</li>
              <li :class="{ met: hasNumber }">One number</li>
            </ul>
          </div>
        </div>

        <!-- Step 3: API/Web Settings -->
        <div v-if="currentStep === 3" class="step-panel">
          <h2>Web Interface Settings</h2>
          <p class="step-description">
            Configure the web interface and API server settings.
          </p>

          <div class="form-group">
            <label>Hostname</label>
            <input
              type="text"
              v-model="config.hostname"
              placeholder="nswall"
              :class="{ error: errors.hostname }"
            >
            <span v-if="errors.hostname" class="error-text">{{ errors.hostname }}</span>
          </div>

          <div class="form-group">
            <label>API/Web Port</label>
            <input
              type="number"
              v-model.number="config.api_port"
              min="1"
              max="65535"
              :class="{ error: errors.api_port }"
            >
            <span class="help-text">Default: 8443. Use 443 for standard HTTPS.</span>
          </div>

          <div class="form-group">
            <label class="checkbox-label">
              <input type="checkbox" v-model="config.enable_https">
              Enable HTTPS (recommended)
            </label>
          </div>

          <div class="form-group">
            <label>Timezone</label>
            <select v-model="config.timezone">
              <option v-for="tz in timezones" :key="tz" :value="tz">{{ tz }}</option>
            </select>
          </div>
        </div>

        <!-- Step 4: DNS Settings -->
        <div v-if="currentStep === 4" class="step-panel">
          <h2>DNS Configuration</h2>
          <p class="step-description">
            Configure DNS servers for name resolution.
          </p>

          <div class="form-group">
            <label>Primary DNS Server</label>
            <input
              type="text"
              v-model="dnsServer1"
              placeholder="1.1.1.1"
            >
          </div>

          <div class="form-group">
            <label>Secondary DNS Server (optional)</label>
            <input
              type="text"
              v-model="dnsServer2"
              placeholder="8.8.8.8"
            >
          </div>

          <div class="dns-presets">
            <label>Quick Presets:</label>
            <div class="preset-buttons">
              <button type="button" @click="setDNSPreset('cloudflare')">Cloudflare</button>
              <button type="button" @click="setDNSPreset('google')">Google</button>
              <button type="button" @click="setDNSPreset('quad9')">Quad9</button>
              <button type="button" @click="setDNSPreset('opendns')">OpenDNS</button>
            </div>
          </div>

          <div class="form-group">
            <label class="checkbox-label">
              <input type="checkbox" v-model="config.enable_local_dns">
              Enable local DNS resolver (Unbound)
            </label>
            <span class="help-text">Provides caching and DNSSEC validation</span>
          </div>
        </div>

        <!-- Step 5: Security Settings -->
        <div v-if="currentStep === 5" class="step-panel">
          <h2>Security Settings</h2>
          <p class="step-description">
            Configure security features for your firewall management.
          </p>

          <div class="security-options">
            <div class="security-option">
              <label class="checkbox-label">
                <input type="checkbox" v-model="config.enable_2fa">
                Require Two-Factor Authentication
              </label>
              <p class="option-description">
                Require TOTP-based 2FA for all admin logins. Can be configured per-user later.
              </p>
            </div>

            <div class="security-option">
              <label class="checkbox-label">
                <input type="checkbox" v-model="config.enable_audit_logging">
                Enable Audit Logging
              </label>
              <p class="option-description">
                Log all configuration changes and login attempts for security compliance.
              </p>
            </div>

            <div class="security-option">
              <label class="checkbox-label">
                <input type="checkbox" v-model="config.enable_api_rate_limit">
                Enable API Rate Limiting
              </label>
              <p class="option-description">
                Protect against brute-force attacks by limiting API requests.
              </p>
            </div>
          </div>
        </div>

        <!-- Summary Step -->
        <div v-if="currentStep === 6" class="step-panel summary">
          <h2>Review Configuration</h2>
          <p class="step-description">
            Review your settings before completing the setup.
          </p>

          <div class="summary-section">
            <h3>Management Interface</h3>
            <div class="summary-item">
              <span class="label">Interface:</span>
              <span>{{ config.management_interface }}</span>
            </div>
            <div class="summary-item">
              <span class="label">IP Configuration:</span>
              <span>{{ config.use_dhcp ? 'DHCP' : config.management_ip }}</span>
            </div>
          </div>

          <div class="summary-section">
            <h3>Administrator</h3>
            <div class="summary-item">
              <span class="label">Username:</span>
              <span>{{ config.admin_username }}</span>
            </div>
            <div class="summary-item" v-if="config.admin_email">
              <span class="label">Email:</span>
              <span>{{ config.admin_email }}</span>
            </div>
          </div>

          <div class="summary-section">
            <h3>Web Interface</h3>
            <div class="summary-item">
              <span class="label">Hostname:</span>
              <span>{{ config.hostname }}</span>
            </div>
            <div class="summary-item">
              <span class="label">Port:</span>
              <span>{{ config.api_port }}</span>
            </div>
            <div class="summary-item">
              <span class="label">HTTPS:</span>
              <span>{{ config.enable_https ? 'Enabled' : 'Disabled' }}</span>
            </div>
          </div>

          <div class="summary-section">
            <h3>Security</h3>
            <div class="summary-item">
              <span class="label">Two-Factor Auth:</span>
              <span>{{ config.enable_2fa ? 'Enabled' : 'Disabled' }}</span>
            </div>
            <div class="summary-item">
              <span class="label">Audit Logging:</span>
              <span>{{ config.enable_audit_logging ? 'Enabled' : 'Disabled' }}</span>
            </div>
          </div>

          <div class="warning-box">
            <strong>Important:</strong> After completing setup, you will be redirected to the login page.
            Use your new admin credentials to log in.
          </div>
        </div>
      </div>

      <!-- Navigation -->
      <div class="step-navigation">
        <button
          v-if="currentStep > 1"
          class="btn btn-secondary"
          @click="previousStep"
          :disabled="loading"
        >
          Previous
        </button>
        <div class="spacer"></div>
        <button
          v-if="currentStep < 6"
          class="btn btn-primary"
          @click="nextStep"
          :disabled="loading || !canProceed"
        >
          {{ loading ? 'Saving...' : 'Next' }}
        </button>
        <button
          v-if="currentStep === 6"
          class="btn btn-success"
          @click="completeSetup"
          :disabled="loading"
        >
          {{ loading ? 'Completing...' : 'Complete Setup' }}
        </button>
      </div>

      <!-- Error Message -->
      <div v-if="errorMessage" class="error-message">
        {{ errorMessage }}
      </div>
    </div>
  </div>
</template>

<script>
import { ref, computed, onMounted, watch } from 'vue'
import { useRouter } from 'vue-router'
import api from '@/services/api'

export default {
  name: 'Setup',
  setup() {
    const router = useRouter()
    const currentStep = ref(1)
    const loading = ref(false)
    const errorMessage = ref('')
    const showPassword = ref(false)
    const confirmPassword = ref('')

    const interfaces = ref([])
    const timezones = ref([])
    const errors = ref({})

    const dnsServer1 = ref('1.1.1.1')
    const dnsServer2 = ref('8.8.8.8')

    const config = ref({
      management_interface: '',
      management_ip: '',
      management_netmask: '255.255.255.0',
      management_gateway: '',
      use_dhcp: true,
      admin_username: 'admin',
      admin_password: '',
      admin_email: '',
      api_port: 8443,
      enable_https: true,
      hostname: 'nswall',
      timezone: 'UTC',
      dns_servers: ['1.1.1.1', '8.8.8.8'],
      enable_local_dns: true,
      enable_2fa: false,
      enable_audit_logging: true,
      enable_api_rate_limit: true
    })

    const steps = [
      { title: 'Interface' },
      { title: 'Admin' },
      { title: 'Web' },
      { title: 'DNS' },
      { title: 'Security' },
      { title: 'Review' }
    ]

    const selectedInterface = computed(() => {
      return interfaces.value.find(i => i.name === config.value.management_interface)
    })

    // Password validation
    const hasMinLength = computed(() => config.value.admin_password.length >= 8)
    const hasUppercase = computed(() => /[A-Z]/.test(config.value.admin_password))
    const hasLowercase = computed(() => /[a-z]/.test(config.value.admin_password))
    const hasNumber = computed(() => /[0-9]/.test(config.value.admin_password))

    const passwordStrength = computed(() => {
      const password = config.value.admin_password
      let score = 0
      if (password.length >= 8) score++
      if (password.length >= 12) score++
      if (/[A-Z]/.test(password)) score++
      if (/[a-z]/.test(password)) score++
      if (/[0-9]/.test(password)) score++
      if (/[^A-Za-z0-9]/.test(password)) score++

      if (score <= 2) return { class: 'weak', width: '33%', label: 'Weak' }
      if (score <= 4) return { class: 'medium', width: '66%', label: 'Medium' }
      return { class: 'strong', width: '100%', label: 'Strong' }
    })

    const canProceed = computed(() => {
      switch (currentStep.value) {
        case 1:
          if (!config.value.management_interface) return false
          if (!config.value.use_dhcp && !config.value.management_ip) return false
          return true
        case 2:
          if (!config.value.admin_username || config.value.admin_username.length < 3) return false
          if (!hasMinLength.value || !hasUppercase.value || !hasLowercase.value || !hasNumber.value) return false
          if (config.value.admin_password !== confirmPassword.value) return false
          return true
        case 3:
          if (!config.value.hostname) return false
          if (config.value.api_port < 1 || config.value.api_port > 65535) return false
          return true
        case 4:
          return dnsServer1.value !== ''
        case 5:
          return true
        default:
          return true
      }
    })

    // Watchers
    watch([dnsServer1, dnsServer2], () => {
      config.value.dns_servers = [dnsServer1.value, dnsServer2.value].filter(Boolean)
    })

    // Methods
    const loadInterfaces = async () => {
      try {
        const response = await api.get('/setup/interfaces')
        interfaces.value = response.data.data
      } catch (error) {
        console.error('Failed to load interfaces:', error)
        errorMessage.value = 'Failed to load network interfaces'
      }
    }

    const loadTimezones = async () => {
      try {
        const response = await api.get('/setup/timezones')
        timezones.value = response.data.data
      } catch (error) {
        console.error('Failed to load timezones:', error)
        timezones.value = ['UTC', 'America/New_York', 'America/Los_Angeles', 'Europe/London']
      }
    }

    const checkSetupStatus = async () => {
      try {
        const response = await api.get('/setup/status')
        if (response.data.data.completed) {
          router.push('/login')
        }
      } catch (error) {
        console.error('Failed to check setup status:', error)
      }
    }

    const onInterfaceSelect = () => {
      const iface = selectedInterface.value
      if (iface && iface.ipv4 && iface.ipv4.length > 0) {
        config.value.management_ip = iface.ipv4[0]
      }
    }

    const setDNSPreset = (preset) => {
      const presets = {
        cloudflare: ['1.1.1.1', '1.0.0.1'],
        google: ['8.8.8.8', '8.8.4.4'],
        quad9: ['9.9.9.9', '149.112.112.112'],
        opendns: ['208.67.222.222', '208.67.220.220']
      }
      const [dns1, dns2] = presets[preset] || ['1.1.1.1', '8.8.8.8']
      dnsServer1.value = dns1
      dnsServer2.value = dns2
    }

    const validateStep = async (step) => {
      errors.value = {}
      try {
        const response = await api.post(`/setup/validate/${step}`, config.value)
        if (!response.data.success) {
          errorMessage.value = response.data.error
          return false
        }
        return true
      } catch (error) {
        errorMessage.value = error.response?.data?.error || 'Validation failed'
        return false
      }
    }

    const saveStep = async (step) => {
      try {
        await api.post(`/setup/step/${step}`, config.value)
        return true
      } catch (error) {
        errorMessage.value = error.response?.data?.error || 'Failed to save step'
        return false
      }
    }

    const nextStep = async () => {
      errorMessage.value = ''
      loading.value = true

      try {
        // Validate and save current step
        if (currentStep.value <= 5) {
          const valid = await validateStep(currentStep.value)
          if (!valid) {
            loading.value = false
            return
          }

          const saved = await saveStep(currentStep.value)
          if (!saved) {
            loading.value = false
            return
          }
        }

        currentStep.value++
      } finally {
        loading.value = false
      }
    }

    const previousStep = () => {
      if (currentStep.value > 1) {
        currentStep.value--
        errorMessage.value = ''
      }
    }

    const completeSetup = async () => {
      loading.value = true
      errorMessage.value = ''

      try {
        await api.post('/setup/complete')

        // Show success and redirect
        alert('Setup completed successfully! You will now be redirected to the login page.')
        router.push('/login')
      } catch (error) {
        errorMessage.value = error.response?.data?.error || 'Failed to complete setup'
      } finally {
        loading.value = false
      }
    }

    onMounted(() => {
      checkSetupStatus()
      loadInterfaces()
      loadTimezones()
    })

    return {
      currentStep,
      loading,
      errorMessage,
      showPassword,
      confirmPassword,
      interfaces,
      timezones,
      errors,
      config,
      steps,
      selectedInterface,
      dnsServer1,
      dnsServer2,
      hasMinLength,
      hasUppercase,
      hasLowercase,
      hasNumber,
      passwordStrength,
      canProceed,
      onInterfaceSelect,
      setDNSPreset,
      nextStep,
      previousStep,
      completeSetup
    }
  }
}
</script>

<style scoped>
.setup-wizard {
  min-height: 100vh;
  background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 2rem;
}

.setup-container {
  background: #1e1e2e;
  border-radius: 12px;
  box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
  max-width: 700px;
  width: 100%;
  padding: 2rem;
}

.setup-header {
  text-align: center;
  margin-bottom: 2rem;
}

.setup-header h1 {
  color: #fff;
  margin: 0;
  font-size: 2rem;
}

.subtitle {
  color: #888;
  margin-top: 0.5rem;
}

/* Progress Steps */
.progress-steps {
  display: flex;
  justify-content: space-between;
  margin-bottom: 2rem;
  position: relative;
}

.progress-steps::before {
  content: '';
  position: absolute;
  top: 15px;
  left: 30px;
  right: 30px;
  height: 2px;
  background: #333;
  z-index: 0;
}

.step {
  display: flex;
  flex-direction: column;
  align-items: center;
  z-index: 1;
}

.step-number {
  width: 32px;
  height: 32px;
  border-radius: 50%;
  background: #333;
  color: #888;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: bold;
  margin-bottom: 0.5rem;
  transition: all 0.3s;
}

.step.active .step-number {
  background: #007bff;
  color: #fff;
}

.step.completed .step-number {
  background: #28a745;
  color: #fff;
}

.step-label {
  color: #888;
  font-size: 0.75rem;
}

.step.active .step-label {
  color: #fff;
}

/* Step Content */
.step-content {
  min-height: 400px;
}

.step-panel {
  animation: fadeIn 0.3s ease;
}

@keyframes fadeIn {
  from { opacity: 0; transform: translateY(10px); }
  to { opacity: 1; transform: translateY(0); }
}

.step-panel h2 {
  color: #fff;
  margin-top: 0;
  margin-bottom: 0.5rem;
}

.step-description {
  color: #888;
  margin-bottom: 1.5rem;
}

/* Form Elements */
.form-group {
  margin-bottom: 1.25rem;
}

.form-group label {
  display: block;
  color: #ccc;
  margin-bottom: 0.5rem;
  font-weight: 500;
}

.form-group input[type="text"],
.form-group input[type="password"],
.form-group input[type="email"],
.form-group input[type="number"],
.form-group select {
  width: 100%;
  padding: 0.75rem;
  background: #2a2a3e;
  border: 1px solid #444;
  border-radius: 6px;
  color: #fff;
  font-size: 1rem;
  transition: border-color 0.3s;
}

.form-group input:focus,
.form-group select:focus {
  outline: none;
  border-color: #007bff;
}

.form-group input.error {
  border-color: #dc3545;
}

.error-text {
  color: #dc3545;
  font-size: 0.85rem;
  margin-top: 0.25rem;
  display: block;
}

.help-text {
  color: #888;
  font-size: 0.85rem;
  margin-top: 0.25rem;
  display: block;
}

.checkbox-label {
  display: flex !important;
  align-items: center;
  gap: 0.5rem;
  cursor: pointer;
}

.checkbox-label input[type="checkbox"] {
  width: auto;
}

/* Interface Details */
.interface-details {
  background: #2a2a3e;
  border-radius: 6px;
  padding: 1rem;
  margin-bottom: 1rem;
}

.detail-row {
  display: flex;
  justify-content: space-between;
  padding: 0.25rem 0;
}

.detail-row .label {
  color: #888;
}

.status.up {
  color: #28a745;
}

.status.down {
  color: #dc3545;
}

/* Password Input */
.password-input {
  display: flex;
  gap: 0.5rem;
}

.password-input input {
  flex: 1;
}

.toggle-password {
  padding: 0.75rem 1rem;
  background: #333;
  border: 1px solid #444;
  border-radius: 6px;
  color: #fff;
  cursor: pointer;
}

.password-strength {
  margin-top: 0.5rem;
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.strength-bar {
  height: 4px;
  border-radius: 2px;
  transition: width 0.3s, background 0.3s;
}

.strength-bar.weak { background: #dc3545; }
.strength-bar.medium { background: #ffc107; }
.strength-bar.strong { background: #28a745; }

.strength-label {
  font-size: 0.85rem;
  color: #888;
}

.password-requirements {
  background: #2a2a3e;
  border-radius: 6px;
  padding: 1rem;
  margin-top: 1rem;
}

.password-requirements p {
  color: #888;
  margin: 0 0 0.5rem 0;
}

.password-requirements ul {
  margin: 0;
  padding-left: 1.5rem;
}

.password-requirements li {
  color: #888;
  margin: 0.25rem 0;
}

.password-requirements li.met {
  color: #28a745;
}

.password-requirements li.met::marker {
  content: '✓ ';
}

/* DNS Presets */
.dns-presets {
  margin: 1rem 0;
}

.dns-presets label {
  color: #888;
  display: block;
  margin-bottom: 0.5rem;
}

.preset-buttons {
  display: flex;
  gap: 0.5rem;
  flex-wrap: wrap;
}

.preset-buttons button {
  padding: 0.5rem 1rem;
  background: #333;
  border: 1px solid #444;
  border-radius: 6px;
  color: #fff;
  cursor: pointer;
  transition: background 0.3s;
}

.preset-buttons button:hover {
  background: #444;
}

/* Security Options */
.security-options {
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
}

.security-option {
  background: #2a2a3e;
  border-radius: 6px;
  padding: 1rem;
}

.option-description {
  color: #888;
  font-size: 0.9rem;
  margin: 0.5rem 0 0 1.75rem;
}

/* Summary */
.summary-section {
  background: #2a2a3e;
  border-radius: 6px;
  padding: 1rem;
  margin-bottom: 1rem;
}

.summary-section h3 {
  color: #fff;
  margin: 0 0 0.75rem 0;
  font-size: 1rem;
  border-bottom: 1px solid #444;
  padding-bottom: 0.5rem;
}

.summary-item {
  display: flex;
  justify-content: space-between;
  padding: 0.25rem 0;
  color: #ccc;
}

.summary-item .label {
  color: #888;
}

.warning-box {
  background: rgba(255, 193, 7, 0.1);
  border: 1px solid #ffc107;
  border-radius: 6px;
  padding: 1rem;
  color: #ffc107;
  margin-top: 1rem;
}

/* Navigation */
.step-navigation {
  display: flex;
  margin-top: 2rem;
  padding-top: 1.5rem;
  border-top: 1px solid #333;
}

.spacer {
  flex: 1;
}

.btn {
  padding: 0.75rem 1.5rem;
  border-radius: 6px;
  font-size: 1rem;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.3s;
  border: none;
}

.btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.btn-primary {
  background: #007bff;
  color: #fff;
}

.btn-primary:hover:not(:disabled) {
  background: #0056b3;
}

.btn-secondary {
  background: #333;
  color: #fff;
}

.btn-secondary:hover:not(:disabled) {
  background: #444;
}

.btn-success {
  background: #28a745;
  color: #fff;
}

.btn-success:hover:not(:disabled) {
  background: #1e7e34;
}

.error-message {
  background: rgba(220, 53, 69, 0.1);
  border: 1px solid #dc3545;
  border-radius: 6px;
  padding: 1rem;
  color: #dc3545;
  margin-top: 1rem;
  text-align: center;
}

/* Responsive */
@media (max-width: 600px) {
  .setup-container {
    padding: 1.5rem;
  }

  .progress-steps {
    overflow-x: auto;
    padding-bottom: 0.5rem;
  }

  .step-label {
    display: none;
  }
}
</style>
