{
  data() {
    return {
      selectedFile: null,
      dragActive: false,
      languages: 'eng,fra',
      loading: false,
      error: null,
      resultText: '',
      copied: false,
    };
  },
  methods: {
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
        formData.append('lang', this.languages || 'eng,fra');

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
