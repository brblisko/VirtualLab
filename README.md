# VirtualLab

The remote laboratory application provides access to the PYNQ devices using tunnel connections.

## Requirements

- The web application requires Nette 3.1, which requires PHP 8.0.
- The application requires an `nfs` server setup with paths to the `UserData` directory entered in `main.go`, the `FileManager.php` model and `AdminPresenter.php` presenter.
- The daemon requires Go 1.21.3.
- The daemon requires RSA keys generated and path to the key entered in `main.go`.
- The daemon needs to be run with elevated privileges to set up `iptables`.
- The application requires `student` profiles to be set up on the server and on PYNQ devices with the same `UID`.
- The web application needs to be run under the `student` profile.

## Installation

### Prerequisites

- Ensure Composer is installed. Follow the instructions on the [Composer website](https://getcomposer.org/download/).
- Ensure Go is installed. Follow the instructions on the [Go website](https://golang.org/doc/install).

### Steps for Web Application

1. **Navigate to the web application directory**:

   ```sh
   cd ./web/
   ```

2. **Install Dependencies**:

   ```sh
   composer install
   ```

3. **Set Up Configuration**:

   - Copy and modify example configuration files as needed.

   ```sh
   cp /path/to/your/local.neon ./config/local.neon
   ```

4. **Set Up Permissions**:

   ```sh
   chmod -R a+rw temp log
   ```

5. **Run the Web Application**:

   ```sh
   php -S localhost:8000 -t www
   ```

   Alternatively, set up a virtual host in your web server.

### Steps for Daemon

1. **Navigate to the daemon directory**:

   ```sh
   cd ./daemon/
   ```

2. **Get Project Dependencies**:

   - Download the necessary dependencies.

   ```sh
   go mod tidy
   ```

3. **Build the Project**:

   - Compile the Go project. This will create an `VLab` executable file in the current directory.

   ```sh
   go build .
   ```

4. **Run the Project**:
   - Run the compiled executable or directly run the main Go file.
   ```sh
   sudo ./VLab
   ```
