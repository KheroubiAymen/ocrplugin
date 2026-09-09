{
  data() {
    return {
      selectedFile: null,
      dragActive: false,
      languages: '',
      loading: false,
      error: null,
      resultText: '',
      copied: false,
    };
  },
  methods: {
    // Same approach as smpl-v2's script.js: getUserRouteURLByName only finds
    // routes linked to this plugin (plugin_id), which DiData's marketplace
    // install never sets for a composer-installed package's routes. Our route
    // is a standalone user route (no plugin_id), so we look it up the way
    // smpl does — via the general /user-routes list — instead.
    async getRouteURLByName(name) {
      if (!this._ocrRouteCache) {
        const routes = await this.dapp.$axios.$get('/user-routes');
        this._ocrRouteCache = {};
        routes.forEach(r => { this._ocrRouteCache[r.name] = r.url; });
      }
      const url = this._ocrRouteCache[name];
      if (!url) {
        throw new Error('Can not find user route with name: ' + name);
      }
      return url;
    },
    onFileChange(e) {
      const file = e.target.files && e.target.files[0];
      if (file) this.setFile(file);
    },
    onDrop(e) {
      this.dragActive = false;
      const file = e.dataTransfer.files && e.dataTransfer.files[0];
      if (file) this.setFile(file);
    },
    setFile(file) {
      this.selectedFile = file;
      this.resultText = '';
      this.error = null;
      this.copied = false;
    },
    async extractText() {
      if (!this.selectedFile) return;
      this.loading = true;
      this.error = null;
      this.resultText = '';
      try {
        const formData = new FormData();
        formData.append('file', this.selectedFile);
        if (this.languages) formData.append('lang', this.languages);

        const url = await this.getRouteURLByName('ocr_extract_text');
        const response = await this.dapp.$axios.$post(url, formData);

        if (response && response.error) {
          this.error = response.error;
        } else {
          this.resultText = (response && response.text) || '';
        }
      } catch (e) {
        this.error = (e && e.message) ? e.message : 'OCR extraction failed.';
      } finally {
        this.loading = false;
      }
    },
    async copyResult() {
      try {
        await navigator.clipboard.writeText(this.resultText);
        this.copied = true;
        setTimeout(() => { this.copied = false; }, 1500);
      } catch (e) {
        // clipboard API unavailable — silently ignore
      }
    },
  },
}
