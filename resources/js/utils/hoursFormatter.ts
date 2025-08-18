/**
 * Indonesian Hours and Minutes Formatter for Frontend
 * Converts decimal hours to "jam menit" format
 */

export interface FormattedHours {
  decimal: number;
  formatted: string;
  compact: string;
  time: string;
}

export class HoursFormatter {
  /**
   * Format decimal hours to Indonesian "jam menit" format
   */
  static formatHoursMinutes(decimalHours: number): string {
    if (decimalHours <= 0) {
      return '0 jam 0 menit';
    }
    
    const hours = Math.floor(decimalHours);
    const minutes = Math.round((decimalHours - hours) * 60);
    
    // Handle rounding edge case where minutes = 60
    let finalHours = hours;
    let finalMinutes = minutes;
    
    if (finalMinutes >= 60) {
      finalHours += 1;
      finalMinutes = 0;
    }
    
    if (finalHours > 0 && finalMinutes > 0) {
      return `${finalHours} jam ${finalMinutes} menit`;
    } else if (finalHours > 0) {
      return `${finalHours} jam`;
    } else {
      return `${finalMinutes} menit`;
    }
  }
  
  /**
   * Format decimal hours to compact "j m" format
   */
  static formatCompact(decimalHours: number): string {
    if (decimalHours <= 0) {
      return '0j 0m';
    }
    
    const hours = Math.floor(decimalHours);
    const minutes = Math.round((decimalHours - hours) * 60);
    
    let finalHours = hours;
    let finalMinutes = minutes;
    
    if (finalMinutes >= 60) {
      finalHours += 1;
      finalMinutes = 0;
    }
    
    return `${finalHours}j ${finalMinutes}m`;
  }
  
  /**
   * Format decimal hours to "HH:MM" time format
   */
  static formatTime(decimalHours: number): string {
    if (decimalHours <= 0) {
      return '00:00';
    }
    
    const hours = Math.floor(decimalHours);
    const minutes = Math.round((decimalHours - hours) * 60);
    
    let finalHours = hours;
    let finalMinutes = minutes;
    
    if (finalMinutes >= 60) {
      finalHours += 1;
      finalMinutes = 0;
    }
    
    return `${finalHours.toString().padStart(2, '0')}:${finalMinutes.toString().padStart(2, '0')}`;
  }
  
  /**
   * Handle API response that might be decimal or formatted object
   */
  static displayHours(hoursData: number | FormattedHours): string {
    if (typeof hoursData === 'number') {
      return this.formatHoursMinutes(hoursData);
    } else if (hoursData && typeof hoursData === 'object') {
      return hoursData.formatted || this.formatHoursMinutes(hoursData.decimal || 0);
    }
    return '0 jam 0 menit';
  }
  
  /**
   * Get compact display for limited space
   */
  static displayCompact(hoursData: number | FormattedHours): string {
    if (typeof hoursData === 'number') {
      return this.formatCompact(hoursData);
    } else if (hoursData && typeof hoursData === 'object') {
      return hoursData.compact || this.formatCompact(hoursData.decimal || 0);
    }
    return '0j 0m';
  }
  
  /**
   * Parse various time formats to decimal hours
   */
  static parseToDecimal(timeString: string): number {
    // Handle "8j 30m" format
    const compactMatch = timeString.match(/(\d+)j\s*(\d+)m/);
    if (compactMatch) {
      const hours = parseInt(compactMatch[1]);
      const minutes = parseInt(compactMatch[2]);
      return hours + (minutes / 60);
    }
    
    // Handle "8 jam 30 menit" format
    const fullMatch = timeString.match(/(\d+)\s*jam\s*(\d+)\s*menit/);
    if (fullMatch) {
      const hours = parseInt(fullMatch[1]);
      const minutes = parseInt(fullMatch[2]);
      return hours + (minutes / 60);
    }
    
    // Handle "HH:MM" format
    const timeMatch = timeString.match(/(\d+):(\d+)/);
    if (timeMatch) {
      const hours = parseInt(timeMatch[1]);
      const minutes = parseInt(timeMatch[2]);
      return hours + (minutes / 60);
    }
    
    // Handle plain decimal string
    const decimal = parseFloat(timeString);
    return isNaN(decimal) ? 0 : decimal;
  }
}